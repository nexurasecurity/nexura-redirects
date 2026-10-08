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
		
		// Hook daily cleanup routine
		add_action( 'nexura_redirects_daily_cleanup', array( __CLASS__, 'cleanup_old_logs' ) );
	}

	/**
	 * Clean up logs older than configured retention period.
	 * Triggered by WP-Cron.
	 */
	public static function cleanup_old_logs() {
		global $wpdb;

		// 1. Redirect logs retention
		$redirect_retention = get_option( 'nexura_redirect_log_retention', 'week' );
		$table_redirect_logs = $wpdb->prefix . 'nexura_redirect_logs';

		if ( 'none' === $redirect_retention ) {
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$wpdb->query( "TRUNCATE TABLE {$table_redirect_logs}" );
		} elseif ( 'forever' !== $redirect_retention ) {
			$days = 7;
			if ( 'day' === $redirect_retention ) {
				$days = 1;
			} elseif ( 'month' === $redirect_retention ) {
				$days = 30;
			}

			$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_redirect_logs} WHERE created_at < %s", $cutoff ) );
		}

		// 2. 404 logs retention
		$log_404_retention = get_option( 'nexura_404_log_retention', 'week' );
		$table_404_logs    = $wpdb->prefix . 'nexura_404_logs';

		if ( 'none' === $log_404_retention ) {
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$wpdb->query( "TRUNCATE TABLE {$table_404_logs}" );
		} elseif ( 'forever' !== $log_404_retention ) {
			$days = 7;
			if ( 'day' === $log_404_retention ) {
				$days = 1;
			} elseif ( 'month' === $log_404_retention ) {
				$days = 30;
			}

			$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_404_logs} WHERE last_seen < %s", $cutoff ) );
		}
	}

	/**
	 * Log a redirect hit.
	 * 
	 * @param int $redirect_id The ID of the redirect.
	 */
	public static function log_redirect( $redirect_id ) {
		// Check retention setting
		$retention = get_option( 'nexura_redirect_log_retention', 'week' );
		if ( $retention === 'none' ) {
			return;
		}

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
		// Check retention setting
		$retention = get_option( 'nexura_404_log_retention', 'week' );
		if ( $retention === 'none' ) {
			return;
		}

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
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_404} SET hits = hits + 1, last_seen = %s, visitor_ip = %s, user_agent = %s, referrer = %s WHERE id = %d", current_time( 'mysql' ), $visitor_ip, $user_agent, $referrer, $existing->id ) );
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
	 * Supports Cloudflare, Proxies, and IPv4/IPv6 validation.
	 * 
	 * @return string The IP address.
	 */
	private static function get_visitor_ip() {
		$ip_logging = get_option( 'nexura_ip_logging', 'full' );
		if ( $ip_logging === 'none' ) {
			return '';
		}

		$ip = '127.0.0.1'; // Default

		// Check Cloudflare Connecting IP first (common on live servers)
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$cf_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
			if ( filter_var( $cf_ip, FILTER_VALIDATE_IP ) ) {
				$ip = $cf_ip;
			}
		} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$client_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			if ( filter_var( $client_ip, FILTER_VALIDATE_IP ) ) {
				$ip = $client_ip;
			}
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip_list   = explode( ',', $forwarded );
			$first_ip  = trim( $ip_list[0] );
			if ( filter_var( $first_ip, FILTER_VALIDATE_IP ) ) {
				$ip = $first_ip;
			}
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$remote_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			if ( filter_var( $remote_ip, FILTER_VALIDATE_IP ) ) {
				$ip = $remote_ip;
			}
		}

		// Use WordPress native IP anonymization for GDPR compliance (WP 4.9.6+).
		if ( $ip_logging === 'anonymized' && function_exists( 'wp_privacy_anonymize_ip' ) ) {
			return wp_privacy_anonymize_ip( $ip );
		}

		return $ip;
	}
}
