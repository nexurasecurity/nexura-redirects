<?php
/**
 * Import & Export Class.
 * Handles migrating redirects via CSV and JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Import_Export {

	/**
	 * Export redirects to JSON.
	 */
	public static function export_json() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirects';

		$redirects = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( "SELECT old_url, new_url, status_code, match_type FROM $table_name", ARRAY_A );

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="nexura-redirects-export.json"' );
		
		echo wp_json_encode( $redirects );
		exit;
	}

	/**
	 * Export redirects to CSV.
	 */
	public static function export_csv() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirects';

		$redirects = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( "SELECT old_url, new_url, status_code, match_type FROM $table_name", ARRAY_A );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="nexura-redirects-export.csv"' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		fputcsv( $output, array( 'Old URL', 'New URL', 'Status Code', 'Match Type' ) );

		foreach ( $redirects as $redirect ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
			fputcsv( $output, $redirect );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Export 404 logs to CSV.
	 */
	public static function export_404_csv() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_404_logs';

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		$logs = $wpdb->get_results( "SELECT url, hits, last_seen, visitor_ip, user_agent, referrer FROM {$table_name} ORDER BY last_seen DESC", ARRAY_A );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="nexura-404-logs-export.csv"' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		fputcsv( $output, array( 'URL', 'Hits', 'Last Seen', 'Visitor IP', 'User Agent', 'Referrer' ) );

		if ( ! empty( $logs ) ) {
			foreach ( $logs as $log ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
				fputcsv( $output, $log );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Auto-detect format (JSON or CSV) and import.
	 *
	 * @param string $content  The raw payload.
	 * @param int    $group_id Target group ID.
	 * @return int Number of imported redirects.
	 */
	public static function import( $content, $group_id = 1 ) {
		$trimmed = trim( (string) $content );
		if ( empty( $trimmed ) ) {
			return 0;
		}

		if ( strpos( $trimmed, '[' ) === 0 || strpos( $trimmed, '{' ) === 0 ) {
			return self::import_json( $trimmed, $group_id );
		}

		return self::import_csv( $trimmed, $group_id );
	}

	/**
	 * Import redirects from JSON content.
	 *
	 * @param string $json_data The JSON payload.
	 * @param int    $group_id  Target group ID.
	 * @return int Number of imported redirects.
	 */
	public static function import_json( $json_data, $group_id = 1 ) {
		$data = json_decode( $json_data, true );
		if ( ! is_array( $data ) ) {
			return 0;
		}

		return self::insert_imported_data( $data, $group_id );
	}

	/**
	 * Import redirects from CSV content.
	 *
	 * @param string $csv_data  The raw CSV payload.
	 * @param int    $group_id  Target group ID.
	 * @return int Number of imported redirects.
	 */
	public static function import_csv( $csv_data, $group_id = 1 ) {
		$lines = preg_split( '/\r\n|\r|\n/', trim( (string) $csv_data ) );
		if ( empty( $lines ) ) {
			return 0;
		}

		$parsed_data = array();
		$header      = null;

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			$row = str_getcsv( $line );
			if ( empty( $row ) || count( $row ) < 2 ) {
				continue;
			}

			// Check if header row
			$first_col = strtolower( trim( $row[0] ) );
			$is_header_col = (
				strpos( $first_col, 'old' ) !== false ||
				strpos( $first_col, 'source' ) !== false ||
				strpos( $first_col, 'from' ) !== false ||
				$first_col === 'url' ||
				strpos( $first_col, 'request' ) !== false ||
				strpos( $first_col, 'origin' ) !== false
			);

			if ( is_null( $header ) && $is_header_col ) {
				$header = array_map( function( $col ) {
					$clean = strtolower( trim( $col ) );
					$clean = str_replace( array( ' ', '-', '_' ), '', $clean );
					return $clean;
				}, $row );
				continue;
			}

			if ( ! is_null( $header ) && count( $header ) === count( $row ) ) {
				$row_assoc = array_combine( $header, $row );
				$old_url   = $row_assoc['oldurl'] ?? $row_assoc['source'] ?? $row_assoc['sourceurl'] ?? $row_assoc['urlfrom'] ?? $row_assoc['request'] ?? $row_assoc['origin'] ?? $row_assoc['from'] ?? $row_assoc['url'] ?? $row[0];
				$new_url   = $row_assoc['newurl'] ?? $row_assoc['target'] ?? $row_assoc['targeturl'] ?? $row_assoc['destination'] ?? $row_assoc['urlto'] ?? $row_assoc['actiondata'] ?? $row_assoc['to'] ?? $row[1];
				$status    = $row_assoc['statuscode'] ?? $row_assoc['code'] ?? $row_assoc['actioncode'] ?? $row_assoc['status'] ?? ( $row[2] ?? 301 );
				$match     = $row_assoc['matchtype'] ?? $row_assoc['type'] ?? ( ( ! empty( $row_assoc['regex'] ) && ( $row_assoc['regex'] === '1' || $row_assoc['regex'] === 'true' ) ) ? 'regex' : ( $row[3] ?? 'url' ) );

				$parsed_data[] = array(
					'old_url'     => $old_url,
					'new_url'     => $new_url,
					'status_code' => $status,
					'match_type'  => $match,
				);
			} else {
				// No header or column mismatch - assume standard format: [0] old_url, [1] new_url, [2] status_code, [3] match_type
				$parsed_data[] = array(
					'old_url'     => $row[0],
					'new_url'     => $row[1],
					'status_code' => isset( $row[2] ) ? $row[2] : 301,
					'match_type'  => isset( $row[3] ) ? $row[3] : 'url',
				);
			}
		}

		return self::insert_imported_data( $parsed_data, $group_id );
	}

	/**
	 * Helper to insert bulk data.
	 * 
	 * @param array $data     Array of redirects.
	 * @param int   $group_id Target group ID.
	 * @return int Count of successful inserts.
	 */
	private static function insert_imported_data( $data, $group_id = 1 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirects';
		$count = 0;
		$group_id = absint( $group_id ) ? absint( $group_id ) : 1;

		foreach ( $data as $row ) {
			if ( empty( $row['old_url'] ) || empty( $row['new_url'] ) ) {
				continue;
			}

			$old_url = sanitize_text_field( trim( $row['old_url'] ) );
			$new_url = sanitize_text_field( trim( $row['new_url'] ) );

			// Ensure old URL starts with / if not regex or external
			if ( strpos( $old_url, 'http://' ) !== 0 && strpos( $old_url, 'https://' ) !== 0 && strpos( $old_url, '^' ) !== 0 && strpos( $old_url, '/' ) !== 0 ) {
				$old_url = '/' . $old_url;
			}

			$status_code = isset( $row['status_code'] ) ? absint( $row['status_code'] ) : 301;
			if ( ! in_array( $status_code, array( 301, 302, 307, 308 ), true ) ) {
				$status_code = 301;
			}

			$match_type = isset( $row['match_type'] ) ? sanitize_text_field( $row['match_type'] ) : 'url';

			// Check if exists
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE old_url = %s", $old_url ) );

			if ( ! $exists ) {
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
				$inserted = $wpdb->insert(
					$table_name,
					array(
						'old_url'       => $old_url,
						'new_url'       => $new_url,
						'status_code'   => $status_code,
						'group_id'      => $group_id,
						'match_type'    => $match_type,
						'last_accessed' => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%d', '%d', '%s', '%s' )
				); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				
				if ( $inserted ) {
					$count++;
				}
			}
		}

		return $count;
	}
}
