<?php
/**
 * Database Schema Class.
 * Handles the creation of custom tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_DB_Schema {

	/**
	 * Create the required custom database tables.
	 * This should be called on plugin activation.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_redirects = $wpdb->prefix . 'nexura_redirects';
		$table_groups    = $wpdb->prefix . 'nexura_redirect_groups';
		$table_logs      = $wpdb->prefix . 'nexura_redirect_logs';
		$table_404s      = $wpdb->prefix . 'nexura_404_logs';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// 1. Redirects Table
		$sql_redirects = "CREATE TABLE $table_redirects (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			old_url varchar(255) NOT NULL,
			new_url varchar(255) NOT NULL,
			status_code int(3) NOT NULL DEFAULT '301',
			group_id bigint(20) unsigned NOT NULL DEFAULT '1',
			match_type varchar(50) NOT NULL DEFAULT 'url',
			hits bigint(20) unsigned NOT NULL DEFAULT '0',
			last_accessed datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY old_url (old_url),
			KEY group_id (group_id)
		) $charset_collate;";

		// 2. Groups Table
		$sql_groups = "CREATE TABLE $table_groups (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			module_id varchar(50) NOT NULL DEFAULT 'wordpress',
			status varchar(20) NOT NULL DEFAULT 'enabled',
			position int(11) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY  (id)
		) $charset_collate;";

		// 3. Success Logs Table
		$sql_logs = "CREATE TABLE $table_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			redirect_id bigint(20) unsigned NOT NULL,
			visitor_ip varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			referrer varchar(255) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY redirect_id (redirect_id)
		) $charset_collate;";

		// 4. 404 Logs Table
		$sql_404s = "CREATE TABLE $table_404s (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url varchar(255) NOT NULL,
			hits bigint(20) unsigned NOT NULL DEFAULT '0',
			last_seen datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			visitor_ip varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			referrer varchar(255) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY url (url)
		) $charset_collate;";

		dbDelta( $sql_redirects );
		dbDelta( $sql_groups );
		dbDelta( $sql_logs );
		dbDelta( $sql_404s );

		// Insert default group if none exists
		if ( ! /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_var( "SELECT COUNT(*) FROM $table_groups" ) ) {
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->insert(
				$table_groups,
				array(
					'name'      => 'Redirections',
					'module_id' => 'wordpress',
				)
			);
		}
	}
}
