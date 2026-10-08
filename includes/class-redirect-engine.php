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
		
		// Strip home path from request uri for matching if WP is installed in a subdirectory.
		$home_path = wp_parse_url( home_url(), PHP_URL_PATH );
		$relative_uri = $request_uri;
		if ( $home_path && $home_path !== '/' && strpos( $request_uri, $home_path ) === 0 ) {
			$relative_uri = substr( $request_uri, strlen( $home_path ) );
			if ( empty( $relative_uri ) || $relative_uri[0] !== '/' ) {
				$relative_uri = '/' . ltrim( $relative_uri, '/' );
			}
		}

		// Build full URL for matching (in case old redirects were saved with domain)
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$protocol = is_ssl() ? 'https://' : 'http://';
		$full_url = $host ? $protocol . $host . $request_uri : '';

		// --- SITE SETTINGS LOGIC ---
		$site_relocate = get_option( 'nexura_site_relocate', '' );
		if ( ! empty( $site_relocate ) ) {
			$relocate_to = rtrim( $site_relocate, '/' ) . $request_uri;
			if ( $full_url !== $relocate_to ) {
				// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				wp_redirect( esc_url_raw( $relocate_to ), 301 );
				exit;
			}
		}

		$force_https      = get_option( 'nexura_site_force_https', 0 );
		$preferred_domain = get_option( 'nexura_site_preferred_domain', 'none' );
		$aliases          = get_option( 'nexura_site_aliases', array() );

		$new_protocol = $protocol;
		$new_host     = $host;
		$needs_canonical_redirect = false;

		// Force HTTPS
		if ( $force_https && $protocol === 'http://' ) {
			$new_protocol = 'https://';
			$needs_canonical_redirect = true;
		}

		// Aliases
		$main_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! empty( $host ) && $host !== $main_host && in_array( $host, $aliases, true ) ) {
			$new_host = $main_host;
			$needs_canonical_redirect = true;
		}

		// Preferred Domain
		if ( $preferred_domain === 'add_www' && strpos( $new_host, 'www.' ) !== 0 ) {
			$new_host = 'www.' . $new_host;
			$needs_canonical_redirect = true;
		} elseif ( $preferred_domain === 'remove_www' && strpos( $new_host, 'www.' ) === 0 ) {
			$new_host = substr( $new_host, 4 );
			$needs_canonical_redirect = true;
		}

		if ( $needs_canonical_redirect && ! empty( $new_host ) ) {
			$canonical_url = $new_protocol . $new_host . $request_uri;
			if ( $full_url !== $canonical_url ) {
				// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				wp_redirect( esc_url_raw( $canonical_url ), 301 );
				exit;
			}
		}
		// --- END SITE SETTINGS LOGIC ---

		// Remove query strings for exact matching if needed (can be advanced later).
		$parsed_uri = wp_parse_url( $request_uri );
		$path_only  = isset( $parsed_uri['path'] ) ? $parsed_uri['path'] : '/';
		
		$parsed_relative = wp_parse_url( $relative_uri );
		$relative_path_only = isset( $parsed_relative['path'] ) ? $parsed_relative['path'] : '/';
		
		$parsed_full = wp_parse_url( $full_url );
		$full_path_only = isset( $parsed_full['scheme'], $parsed_full['host'] ) ? $parsed_full['scheme'] . '://' . $parsed_full['host'] . $path_only : '';

		$ignore_slash   = (bool) get_option( 'nexura_default_ignore_slash', 1 );
		$query_matching = get_option( 'nexura_default_query_matching', 'exact' );

		// 1. Build candidates for matching
		$candidates = array( $request_uri, $relative_uri, $full_url );
		if ( $request_uri !== $path_only ) {
			$candidates[] = $path_only;
			$candidates[] = $relative_path_only;
			if ( ! empty( $full_path_only ) ) {
				$candidates[] = $full_path_only;
			}
		}

		if ( $ignore_slash ) {
			$slash_variants = array();
			foreach ( $candidates as $candidate ) {
				$slash_variants[] = untrailingslashit( $candidate );
				$slash_variants[] = trailingslashit( $candidate );
			}
			$candidates = array_merge( $candidates, $slash_variants );
		}

		$candidates = array_values( array_unique( array_filter( $candidates ) ) );

		$redirect = null;
		if ( ! empty( $candidates ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $candidates ), '%s' ) );
			/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$redirect = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE old_url IN ($placeholders) AND status_code > 0 LIMIT 1", $candidates ) );
		}

		// 2. Regex Matching if no exact/path rule matched
		if ( ! $redirect ) {
			// Fetch all regex redirects
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$regex_redirects = $wpdb->get_results( "SELECT * FROM {$table_name} WHERE match_type LIKE '%regex%' AND status_code > 0" );
			
			if ( $regex_redirects ) {
				foreach ( $regex_redirects as $rule ) {
					$pattern = $rule->old_url;
					// Add delimiters if missing
					if ( substr( $pattern, 0, 1 ) !== '@' && substr( $pattern, 0, 1 ) !== '/' && substr( $pattern, 0, 1 ) !== '#' ) {
						$pattern = '@' . str_replace( '@', '\@', $pattern ) . '@i';
					}
					
					$test_urls = array( $request_uri, $relative_uri, $path_only, $full_url );
					if ( $ignore_slash ) {
						$test_urls[] = untrailingslashit( $path_only );
						$test_urls[] = trailingslashit( $path_only );
					}
					
					foreach ( array_unique( $test_urls ) as $url ) {
						// Suppress warnings in case of malformed user regex
						if ( @preg_match( $pattern, $url, $matches ) ) {
							$redirect = clone $rule;
							// Process capture groups ($1, $2, etc.)
							if ( strpos( $redirect->new_url, '$' ) !== false && count( $matches ) > 1 ) {
								for ( $i = 1; $i < count( $matches ); $i++ ) {
									$redirect->new_url = str_replace( '$' . $i, $matches[$i], $redirect->new_url );
								}
							}
							break 2;
						}
					}
				}
			}
		}

		// Pass query parameters to target if enabled
		if ( $redirect && $query_matching === 'pass' && ! empty( $parsed_uri['query'] ) ) {
			$query_args = array();
			wp_parse_str( $parsed_uri['query'], $query_args );
			if ( ! empty( $query_args ) ) {
				$redirect->new_url = add_query_arg( $query_args, $redirect->new_url );
			}
		}

		// Loop prevention check: if the redirect target is identical to the current requested URL.
		if ( $redirect ) {
			$target_host = wp_parse_url( $redirect->new_url, PHP_URL_HOST );
			$current_host = wp_parse_url( home_url(), PHP_URL_HOST );
			$is_internal = empty( $target_host ) || ( $target_host === $current_host );

			if ( $is_internal ) {
				$target_path = untrailingslashit( wp_make_link_relative( $redirect->new_url ) );
				$curr_path   = untrailingslashit( $relative_path_only );
				if ( $target_path === $curr_path ) {
					return;
				}
			}
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

		// Update hit count and last accessed time.
		$table_name = $wpdb->prefix . 'nexura_redirects';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET hits = hits + 1, last_accessed = %s WHERE id = %d", current_time( 'mysql' ), $redirect->id ) );

		// Log the hit if logger class exists.
		if ( class_exists( 'Nexura_Redirects_Logger' ) ) {
			Nexura_Redirects_Logger::log_redirect( $redirect->id );
		}

		// Add custom WordPress redirect header
		if ( ! headers_sent() ) {
			header( 'X-Redirect-By: Nexura Redirects' );
		}

		// Allow external domain redirection safely via allowed_redirect_hosts filter
		$target_host = wp_parse_url( $new_url, PHP_URL_HOST );
		if ( ! empty( $target_host ) ) {
			add_filter( 'allowed_redirect_hosts', function( $hosts ) use ( $target_host ) {
				if ( is_array( $hosts ) ) {
					$hosts[] = $target_host;
				}
				return $hosts;
			} );
		}

		// Perform redirect
		wp_safe_redirect( esc_url_raw( $new_url ), $status_code );
		exit;
	}
}
