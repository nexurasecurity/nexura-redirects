<?php
/**
 * 404 Logs List Table.
 * Uses WP_List_Table to display 404 errors.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Nexura_Redirects_404_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => __( '404 Error', 'nexura-redirects' ),
			'plural'   => __( '404 Errors', 'nexura-redirects' ),
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'url'        => __( 'URL', 'nexura-redirects' ),
			'hits'       => __( 'Hits', 'nexura-redirects' ),
			'last_seen'  => __( 'Last Seen', 'nexura-redirects' ),
			'visitor_ip' => __( 'Last IP', 'nexura-redirects' ),
			'user_agent' => __( 'User Agent', 'nexura-redirects' ),
			'referrer'   => __( 'Referrer', 'nexura-redirects' ),
		);
		return $columns;
	}

	protected function get_sortable_columns() {
		$sortable_columns = array(
			'url'       => array( 'url', false ),
			'hits'      => array( 'hits', false ),
			'last_seen' => array( 'last_seen', false ),
		);
		return $sortable_columns;
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'url':
			case 'hits':
			case 'last_seen':
			case 'visitor_ip':
			case 'user_agent':
			case 'referrer':
				return esc_html( $item[ $column_name ] );
			default:
				return '';
		}
	}

	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete-404[]" value="%s" />',
			$item['id']
		);
	}

	protected function column_url( $item ) {
		$delete_nonce = wp_create_nonce( 'nexura_delete_404' );
		$add_redirect_nonce = wp_create_nonce( 'nexura_add_redirect_from_404' );
		
		$title = '<strong>' . esc_html( $item['url'] ) . '</strong>';
		
		$actions = array(
			'add'    => sprintf( '<a href="?page=%s&tab=redirects&action=%s&url=%s&_wpnonce=%s">Add Redirect</a>', esc_attr( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['page'] ) ) : '' ), 'add_from_404', urlencode( $item['url'] ), $add_redirect_nonce ),
			'delete' => sprintf( '<a href="?page=%s&tab=404s&action=%s&log=%s&_wpnonce=%s">Delete</a>', esc_attr( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['page'] ) ) : '' ), 'delete', absint( $item['id'] ), $delete_nonce ),
		);

		return $title . $this->row_actions( $actions );
	}

	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete-404' => 'Delete',
		);
		return $actions;
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_404_logs';

		$per_page = 20;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Fetch Data
		$query = "SELECT * FROM {$table_name}";
		$args  = array();
		
		// Sorting
		$orderby = ( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['orderby'] ) ) : 'last_seen';
		$order   = ( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['order'] ) ) : 'DESC';
		
		// Validating orderby and order
		$valid_columns = array( 'url', 'hits', 'last_seen', 'id' );
		$orderby = in_array( $orderby, $valid_columns, true ) ? $orderby : 'last_seen';
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		$query .= " ORDER BY {$orderby} {$order}";

		// Pagination
		$current_page = $this->get_pagenum();
		$total_items  = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name}" );
		
		$offset = ( $current_page - 1 ) * $per_page;
		$query .= " LIMIT %d OFFSET %d";
		$args[] = $per_page;
		$args[] = $offset;
		$this->items = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->prepare( $query, ...$args ), ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
