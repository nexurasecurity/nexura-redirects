<?php
/**
 * Logger Class.
 * Handles tracking of 404 errors and redirect hits.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Logger {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook into template_redirect to catch 404s (Run late, priority 99).
		add_action( 'template_redirect', array( $this, 'track_404_errors' ), 99 );
	}

	/**
	 * Log a redirect hit.
	 * 
	 * @param int $redirect_id The ID of the redirect.
	 */
	public static function log_redirect( $redirect_id ) {
		global $wpdb;
		$table_logs = $wpdb->prefix . 'nexura_redirect_logs';

		// Get visitor info safely.
		$visitor_ip = self::get_visitor_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$referrer   = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->insert(
			$table_logs,
			array(
				'redirect_id' => $redirect_id,
				'visitor_ip'  => $visitor_ip,
				'user_agent'  => $user_agent,
				'referrer'    => $referrer,
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Track 404 errors on the site.
	 */
	public function track_404_errors() {
		// Only track if it's a 404 and not in admin.
		if ( ! is_404() || is_admin() ) {
			return;
		}

		global $wpdb;
		$table_404 = $wpdb->prefix . 'nexura_404_logs';

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$visitor_ip  = self::get_visitor_ip();
		$user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$referrer    = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		// Check if this 404 URL already exists in the log.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table_404} WHERE url = %s LIMIT 1", $request_uri ) );

		if ( $existing ) {
			// Update hits and last seen.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_404} SET hits = hits + 1, last_seen = current_timestamp(), visitor_ip = %s, user_agent = %s, referrer = %s WHERE id = %d", $visitor_ip, $user_agent, $referrer, $existing->id ) );
		} else {
			// Insert new 404 record.
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->insert(
				$table_404,
				array(
					'url'        => $request_uri,
					'visitor_ip' => $visitor_ip,
					'user_agent' => $user_agent,
					'referrer'   => $referrer,
				),
				array( '%s', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Get visitor IP address safely.
	 * 
	 * @return string The IP address.
	 */
	private static function get_visitor_ip() {
		$ip = '127.0.0.1'; // Default

		if ( ! empty( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ?? '' ) ) ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ?? '' ) );
		} elseif ( ! empty( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '' ) ) ) ) {
			// Can sometimes contain multiple IPs comma-separated.
			$ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '' ) ) );
			$ip = trim( $ip_list[0] );
		} elseif ( ! empty( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		}

		// Use WordPress native IP anonymization for GDPR compliance (WP 4.9.6+).
		$ip = sanitize_text_field( wp_unslash( $ip ) );
		return function_exists( 'wp_privacy_anonymize_ip' ) ? wp_privacy_anonymize_ip( $ip ) : $ip;
	}
}
