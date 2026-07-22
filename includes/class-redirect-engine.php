<?php
/**
 * Redirect Engine Class.
 * Handles the actual interception and redirection of URLs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Engine {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Priority 1 to run before standard WP routing (like 404 detection).
		add_action( 'template_redirect', array( $this, 'process_redirects' ), 1 );
	}

	/**
	 * Check current URL against database and redirect if a match is found.
	 */
	public function process_redirects() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirects';

		// Get requested URL (relative path).
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		
		// Build full URL for matching (in case old redirects were saved with domain)
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$protocol = is_ssl() ? 'https://' : 'http://';
		$full_url = $host ? $protocol . $host . $request_uri : '';

		// Remove query strings for exact matching if needed (can be advanced later).
		$parsed_uri = wp_parse_url( $request_uri );
		$path_only  = isset( $parsed_uri['path'] ) ? $parsed_uri['path'] : '/';
		
		$parsed_full = wp_parse_url( $full_url );
		$full_path_only = isset( $parsed_full['scheme'], $parsed_full['host'] ) ? $parsed_full['scheme'] . '://' . $parsed_full['host'] . $path_only : '';

		// 1. Try Exact Match First (Fastest)
		/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared */
		$query = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE (old_url = %s OR old_url = %s) AND status_code > 0 LIMIT 1", $request_uri, $full_url );
		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		$redirect = $wpdb->get_row( $query );

		// 2. If no exact match on full URI, try matching just the path
		if ( ! $redirect && $request_uri !== $path_only ) {
			/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared */
			$query_path = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE (old_url = %s OR old_url = %s) AND status_code > 0 LIMIT 1", $path_only, $full_path_only );
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$redirect = $wpdb->get_row( $query_path );
		}

		// (Phase 2: Add Regex and Wildcard matching here later)

		// Loop prevention check: if the redirect target is the same as the current URL.
		if ( $redirect && wp_make_link_relative( $redirect->new_url ) === $request_uri ) {
			return;
		}

		// If match found, perform the redirect.
		if ( $redirect ) {
			$this->perform_redirect( $redirect );
		}
	}

	/**
	 * Perform the actual HTTP redirect and log it.
	 *
	 * @param object $redirect The redirect database row.
	 */
	private function perform_redirect( $redirect ) {
		global $wpdb;
		
		$new_url     = $redirect->new_url;
		$status_code = (int) $redirect->status_code;
		
		// Ensure valid status code, fallback to 301.
		$valid_statuses = array( 301, 302, 307, 308 );
		if ( ! in_array( $status_code, $valid_statuses, true ) ) {
			$status_code = 301;
		}

		// Update hit count and last accessed time asynchronously or directly.
		$table_name = $wpdb->prefix . 'nexura_redirects';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET hits = hits + 1, last_accessed = current_timestamp() WHERE id = %d", $redirect->id ) );

		// Log the hit if logger class exists (Phase 1 task).
		if ( class_exists( 'Nexura_Redirects_Logger' ) ) {
			Nexura_Redirects_Logger::log_redirect( $redirect->id );
		}

		// Perform redirect
		wp_safe_redirect( esc_url_raw( $new_url ), $status_code );
		exit;
	}
}
