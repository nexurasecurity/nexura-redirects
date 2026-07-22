<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Nexura_Redirects
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$nexura_table_redirects = $wpdb->prefix . 'nexura_redirects';
$nexura_table_logs      = $wpdb->prefix . 'nexura_redirect_logs';
$nexura_table_404s      = $wpdb->prefix . 'nexura_404_logs';

/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared */
$wpdb->query( "DROP TABLE IF EXISTS {$nexura_table_redirects}" );

/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared */
$wpdb->query( "DROP TABLE IF EXISTS {$nexura_table_logs}" );

/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared */
$wpdb->query( "DROP TABLE IF EXISTS {$nexura_table_404s}" );

// Note: Future feature options should be deleted here using delete_option() if added later.
