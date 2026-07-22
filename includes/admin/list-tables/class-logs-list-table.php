<?php
/**
 * Logs List Table Class.
 * Uses WP_List_Table to display successful redirects logs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Nexura_Redirects_Logs_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Log', 'nexura-redirects' ),
			'plural'   => __( 'Logs', 'nexura-redirects' ),
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'created_at' => __( 'Date', 'nexura-redirects' ),
			'source_url' => __( 'Source URL', 'nexura-redirects' ),
			'target_url' => __( 'Target URL', 'nexura-redirects' ),
			'ip_address' => __( 'IP Address', 'nexura-redirects' ),
			'user_agent' => __( 'User Agent', 'nexura-redirects' ),
		);
		return $columns;
	}

	protected function get_sortable_columns() {
		$sortable_columns = array(
			'created_at' => array( 'created_at', false ),
			'source_url' => array( 'source_url', false ),
			'target_url' => array( 'target_url', false ),
		);
		return $sortable_columns;
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
			case 'source_url':
			case 'target_url':
			case 'ip_address':
			case 'user_agent':
				return esc_html( $item[ $column_name ] );
			default:
				return '';
		}
	}

	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete-log[]" value="%s" />',
			$item['id']
		);
	}

	protected function column_created_at( $item ) {
		$delete_nonce = wp_create_nonce( 'nexura_delete_log' );
		
		$title = '<strong>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) ) . '</strong>';
		
		$actions = array(
			'delete' => sprintf( '<a href="?page=%s&tab=log&action=%s&log=%s&_wpnonce=%s">Delete</a>', esc_attr( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['page'] ?? '' ) ) ), 'delete', absint( $item['id'] ), $delete_nonce ),
		);

		return $title . $this->row_actions( $actions );
	}

	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete-log' => 'Delete',
		);
		return $actions;
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirect_logs';

		$per_page = 20;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Process single delete
		if ( 'delete' === $this->current_action() ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'nexura_delete_log' ) ) {
				die( 'Security check failed' );
			}
			$log_id = isset( $_GET['log'] ) ? absint( $_GET['log'] ) : 0;
			if ( $log_id ) {
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->delete( $table_name, array( 'id' => $log_id ) );
			}
		}

		// Process bulk delete
		if ( 'bulk-delete-log' === $this->current_action() ) {
			$log_ids = isset( $_REQUEST['bulk-delete-log'] ) ? array_map( 'absint', (array) $_REQUEST['bulk-delete-log'] ) : array();
			foreach ( $log_ids as $id ) {
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->delete( $table_name, array( 'id' => $id ) );
			}
		}

		// Fetch Data
		$table_redirects = $wpdb->prefix . 'nexura_redirects';
		$query = "SELECT l.id, l.created_at, l.visitor_ip as ip_address, l.user_agent, r.old_url as source_url, r.new_url as target_url 
				  FROM $table_name l 
				  LEFT JOIN $table_redirects r ON l.redirect_id = r.id";
		
		// Search
		if ( ! empty( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['s'] ?? '' ) ) ) ) {
			$search = esc_sql( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['s'] ?? '' ) ) );
			$query .= " WHERE r.old_url LIKE '%{$search}%' OR r.new_url LIKE '%{$search}%'";
		}

		// Sorting
		$orderby = ( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['orderby'] ) ) : 'created_at';
		$order   = ( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['order'] ) ) : 'DESC';
		
		// Validating orderby and order
		$valid_columns = array( 'created_at', 'source_url', 'target_url', 'id' );
		$orderby = in_array( $orderby, $valid_columns, true ) ? $orderby : 'created_at';
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		$query .= " ORDER BY $orderby $order";

		// Pagination
		$total_items = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_var( "SELECT COUNT(id) FROM ($query) AS count_table" );
		
		$current_page = $this->get_pagenum();
		
		$offset = ( $current_page - 1 ) * $per_page;
		$query .= /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );

		$this->items = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( $query, ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
