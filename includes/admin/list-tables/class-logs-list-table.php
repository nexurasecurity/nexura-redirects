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
			'singular' => 'log',
			'plural'   => 'logs',
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
		if ( 'delete' === $this->current_action() && isset( $_GET['log'] ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'nexura_delete_log' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'nexura-redirects' ) );
			}
			$log_id = absint( $_GET['log'] );
			if ( $log_id ) {
				$wpdb->delete( $table_name, array( 'id' => $log_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}

		// Process bulk delete with CSRF verification
		if ( 'bulk-delete-log' === $this->current_action() && isset( $_REQUEST['bulk-delete-log'] ) ) {
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( esc_html__( 'Security check failed.', 'nexura-redirects' ) );
			}
			$log_ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['bulk-delete-log'] ) );
			if ( ! empty( $log_ids ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id IN ($placeholders)", ...$log_ids ) );
			}
		}

		// Fetch Data
		$table_redirects = $wpdb->prefix . 'nexura_redirects';
		$where_clauses   = array();
		$where_params    = array();
		
		// Search
		if ( ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clauses[] = "(r.old_url LIKE %s OR r.new_url LIKE %s)";
			$where_params[] = $search_like;
			$where_params[] = $search_like;
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Count total items efficiently without subquery
		$count_query = "SELECT COUNT(l.id) FROM {$table_name} l LEFT JOIN {$table_redirects} r ON l.redirect_id = r.id{$where_sql}";
		if ( ! empty( $where_params ) ) {
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$total_items = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $where_params ) );
		} else {
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$total_items = (int) $wpdb->get_var( $count_query );
		}

		// Sorting
		$orderby = ( isset( $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = ( isset( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		
		$orderby_map = array(
			'created_at' => 'l.created_at',
			'source_url' => 'r.old_url',
			'target_url' => 'r.new_url',
			'id'         => 'l.id',
		);
		$order_col = isset( $orderby_map[ $orderby ] ) ? $orderby_map[ $orderby ] : 'l.created_at';
		$order     = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;

		$query = "SELECT l.id, l.created_at, l.visitor_ip as ip_address, l.user_agent, r.old_url as source_url, r.new_url as target_url 
				  FROM {$table_name} l 
				  LEFT JOIN {$table_redirects} r ON l.redirect_id = r.id{$where_sql} 
				  ORDER BY {$order_col} {$order} LIMIT %d OFFSET %d";

		$query_params = array_merge( $where_params, array( $per_page, $offset ) );
		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		$this->items = $wpdb->get_results( $wpdb->prepare( $query, $query_params ), ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
