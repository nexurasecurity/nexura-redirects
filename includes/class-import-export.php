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
	 * Import redirects from JSON content.
	 *
	 * @param string $json_data The JSON payload.
	 * @return int Number of imported redirects.
	 */
	public static function import_json( $json_data ) {
		$data = json_decode( $json_data, true );
		if ( ! is_array( $data ) ) {
			return 0;
		}

		return self::insert_imported_data( $data );
	}

	/**
	 * Helper to insert bulk data.
	 * 
	 * @param array $data Array of redirects.
	 * @return int Count of successful inserts.
	 */
	private static function insert_imported_data( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirects';
		$count = 0;

		foreach ( $data as $row ) {
			if ( empty( $row['old_url'] ) || empty( $row['new_url'] ) ) {
				continue;
			}

			$status_code = isset( $row['status_code'] ) ? absint( $row['status_code'] ) : 301;
			$match_type  = isset( $row['match_type'] ) ? sanitize_text_field( $row['match_type'] ) : 'exact';

			// Check if exists
			$exists = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_var( /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->prepare( "SELECT id FROM $table_name WHERE old_url = %s", $row['old_url'] ) );

			if ( ! $exists ) {
				$inserted = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->insert(
					$table_name,
					array(
						'old_url'     => sanitize_text_field( $row['old_url'] ),
						'new_url'     => sanitize_text_field( $row['new_url'] ),
						'status_code' => $status_code,
						'match_type'  => $match_type,
					)
				);
				
				if ( $inserted ) {
					$count++;
				}
			}
		}

		return $count;
	}
}
