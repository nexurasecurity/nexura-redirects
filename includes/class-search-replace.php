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
		
		$report = array(
			'tables'       => 0,
			'rows_checked' => 0,
			'rows_changed' => 0,
			'dry_run'      => $dry_run,
		);

		if ( empty( $search ) || empty( $tables ) ) {
			return $report;
		}

		foreach ( $tables as $table ) {
			// Skip our own logs tables to avoid polluting the migration.
			if ( strpos( $table, 'nexura_redirect_logs' ) !== false || strpos( $table, 'nexura_404_logs' ) !== false ) {
				continue;
			}

			$report['tables']++;
			
			// Get primary key and columns
			$primary_key = '';
			$columns     = array();
			$fields      = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( "DESCRIBE $table" );
			
			foreach ( $fields as $field ) {
				$columns[] = $field->Field;
				if ( 'PRI' === $field->Key ) {
					$primary_key = $field->Field;
				}
			}

			if ( empty( $primary_key ) ) {
				continue; // Cannot safely update without PK.
			}

			// Get data (Added LIMIT 5000 to prevent memory exhaustion, can be expanded to full pagination later)
			$rows = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( "SELECT * FROM $table LIMIT 5000", ARRAY_A );

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
						/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ 
						$wpdb->update( $table, $update_data, $where );
					}
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
				if ( false !== $unserialized && is_array( $unserialized ) || is_object( $unserialized ) ) {
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
