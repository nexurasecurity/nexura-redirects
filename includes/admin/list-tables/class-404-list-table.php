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
			'singular' => '404',
			'plural'   => '404s',
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
			'bulk-delete-404' => __( 'Delete', 'nexura-redirects' ),
		);
		return $actions;
	}

	/**
	 * Process single and bulk delete actions.
	 */
	public function process_bulk_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_404_logs';

		// Single delete
		if ( 'delete' === $this->current_action() && isset( $_GET['log'] ) ) {
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'nexura_delete_404' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'nexura-redirects' ) );
			}

			$log_id = absint( $_GET['log'] );
			if ( $log_id ) {
				$wpdb->delete( $table_name, array( 'id' => $log_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}

		// Bulk delete
		if ( 'bulk-delete-404' === $this->current_action() && isset( $_REQUEST['bulk-delete-404'] ) ) {
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( esc_html__( 'Security check failed.', 'nexura-redirects' ) );
			}

			$ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['bulk-delete-404'] ) );
			if ( ! empty( $ids ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id IN ($placeholders)", ...$ids ) );
			}
		}
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_404_logs';

		$per_page = 20;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		// Base query
		$where_clauses = array();
		$where_values  = array();

		// Search
		if ( ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$where_clauses[] = 'url LIKE %s';
			$where_values[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Count total items efficiently without subquery
		$count_sql = "SELECT COUNT(*) FROM {$table_name}{$where_sql}";
		if ( ! empty( $where_values ) ) {
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$total_items = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $where_values ) );
		} else {
			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$total_items = (int) $wpdb->get_var( $count_sql );
		}

		// Sorting
		$orderby = ( isset( $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'last_seen'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = ( isset( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		
		$valid_columns = array( 'url', 'hits', 'last_seen', 'id' );
		$orderby = in_array( $orderby, $valid_columns, true ) ? $orderby : 'last_seen';
		$order   = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$query = "SELECT * FROM {$table_name}{$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_params = array_merge( $where_values, array( $per_page, $offset ) );

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		$this->items = $wpdb->get_results( $wpdb->prepare( $query, $query_params ), ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
