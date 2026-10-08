<?php
/**
 * Safe Search & Replace Class.
 * Handles database migration by safely replacing URLs inside serialized arrays.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Search_Replace {

	/**
	 * Run the Search and Replace operation.
	 *
	 * @param string $search  The old string (e.g., old domain).
	 * @param string $replace The new string (e.g., new domain).
	 * @param array  $tables  Array of database tables to process.
	 * @param bool   $dry_run If true, no database changes are made.
	 * @return array Results array containing changed rows and tables.
	 */
	public static function run( $search, $replace, $tables, $dry_run = true ) {
		global $wpdb;
		
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 300 );
		}

		$report = array(
			'tables'       => 0,
			'rows_checked' => 0,
			'rows_changed' => 0,
			'dry_run'      => $dry_run,
		);

		if ( empty( $search ) || empty( $tables ) || ! is_array( $tables ) ) {
			return $report;
		}

		// Ensure tables are valid existing tables
		$existing_tables = $wpdb->get_col( "SHOW TABLES" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $tables as $table ) {
			$table = sanitize_text_field( $table );
			if ( ! in_array( $table, $existing_tables, true ) ) {
				continue;
			}

			// Skip our own logs tables to avoid polluting the migration.
			if ( strpos( $table, 'nexura_redirect_logs' ) !== false || strpos( $table, 'nexura_404_logs' ) !== false ) {
				continue;
			}

			$report['tables']++;
			
			// Get primary key and columns
			$primary_key = '';
			$columns     = array();
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$fields      = $wpdb->get_results( "DESCRIBE {$table}" );
			
			foreach ( $fields as $field ) {
				$columns[] = $field->Field;
				if ( 'PRI' === $field->Key ) {
					$primary_key = $field->Field;
				}
			}

			if ( empty( $primary_key ) ) {
				continue; // Cannot safely update without PK.
			}

			// Process table in memory-safe chunks of 500
			$chunk_size = 500;
			$offset     = 0;

			while ( true ) {
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} LIMIT %d OFFSET %d", $chunk_size, $offset ), ARRAY_A );

				if ( empty( $rows ) ) {
					break;
				}

				foreach ( $rows as $row ) {
					$report['rows_checked']++;
					$update_needed = false;
					$update_data   = array();

					foreach ( $columns as $column ) {
						$current_val = $row[ $column ];
						
						// Skip if empty or numeric
						if ( ! is_string( $current_val ) || empty( $current_val ) || is_numeric( $current_val ) ) {
							continue;
						}

						// Process value
						$new_val = self::recursive_replace( $search, $replace, $current_val );

						if ( $new_val !== $current_val ) {
							$update_needed = true;
							$update_data[ $column ] = $new_val;
						}
					}

					// If changes needed, update database (unless dry run)
					if ( $update_needed ) {
						$report['rows_changed']++;
						
						if ( ! $dry_run ) {
							$where = array( $primary_key => $row[ $primary_key ] );
							$wpdb->update( $table, $update_data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						}
					}
				}

				$offset += $chunk_size;
				if ( count( $rows ) < $chunk_size ) {
					break;
				}
			}
		}

		return $report;
	}

	/**
	 * Recursively replace string, protecting serialized data.
	 *
	 * @param string $search  String to look for.
	 * @param string $replace String to replace with.
	 * @param mixed  $data    The data to process.
	 * @return mixed Processed data.
	 */
	private static function recursive_replace( $search, $replace, $data ) {
		if ( is_string( $data ) ) {
			// Check if serialized
			if ( is_serialized( $data ) ) {
				$unserialized = maybe_unserialize( $data );
				if ( false !== $unserialized && ( is_array( $unserialized ) || is_object( $unserialized ) ) ) {
					$unserialized = self::recursive_replace( $search, $replace, $unserialized );
					return serialize( $unserialized );
				}
			}
			// Normal string replacement
			return str_replace( $search, $replace, $data );
		}

		if ( is_array( $data ) ) {
			$new_array = array();
			foreach ( $data as $key => $value ) {
				$new_array[ $key ] = self::recursive_replace( $search, $replace, $value );
			}
			return $new_array;
		}

		if ( is_object( $data ) ) {
			$new_obj = clone $data;
			foreach ( $data as $key => $value ) {
				$new_obj->$key = self::recursive_replace( $search, $replace, $value );
			}
			return $new_obj;
		}

		return $data;
	}
}
