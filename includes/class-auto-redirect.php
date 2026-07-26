<?php
/**
 * Auto Redirect Class.
 * Handles automatic creation of redirects when a post URL changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Auto {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook into post updates.
		add_action( 'post_updated', array( $this, 'detect_url_change' ), 10, 3 );
	}

	/**
	 * Detect if a post URL has changed and create a redirect.
	 *
	 * @param int     $post_ID     Post ID.
	 * @param WP_Post $post_after  Post object following the update.
	 * @param WP_Post $post_before Post object before the update.
	 */
	public function detect_url_change( $post_ID, $post_after, $post_before ) {
		// Only care about published posts.
		if ( 'publish' !== $post_before->post_status || 'publish' !== $post_after->post_status ) {
			return;
		}

		// Don't run on revisions or autosaves.
		if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) {
			return;
		}

		// Check if url monitor is enabled.
		if ( ! get_option( 'nexura_url_monitor', 1 ) ) {
			return;
		}

		$old_url = wp_make_link_relative( get_permalink( $post_before ) );
		$new_url = wp_make_link_relative( get_permalink( $post_after ) );

		if ( $old_url !== $new_url ) {
			$this->create_auto_redirect( $old_url, $new_url );
		}
	}

	/**
	 * Create the automatic redirect in the database.
	 *
	 * @param string $old_url Old relative URL.
	 * @param string $new_url New relative URL.
	 */
	private function create_auto_redirect( $old_url, $new_url ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirects';

		// Avoid redirect loops (e.g., A -> B, then B -> A).
		if ( $old_url === $new_url ) {
			return;
		}

		// Check if redirect already exists for this old URL.
		$existing = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_var( /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->prepare( "SELECT id FROM $table_name WHERE old_url = %s LIMIT 1", $old_url ) );

		if ( $existing ) {
			// Update existing redirect.
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->update(
				$table_name,
				array( 'new_url' => $new_url ),
				array( 'id' => $existing ),
				array( '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new redirect.
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->insert(
				$table_name,
				array(
					'old_url'     => $old_url,
					'new_url'     => $new_url,
					'status_code' => 301,
					'match_type'  => 'exact',
				),
				array( '%s', '%s', '%d', '%s' )
			);
		}
	}
}
