<?php
/**
 * Groups List Table Class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Nexura_Redirects_Groups_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Group', 'nexura-redirects' ),
			'plural'   => __( 'Groups', 'nexura-redirects' ),
			'ajax'     => false
		) );
	}

	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'name'      => __( 'Name', 'nexura-redirects' ),
			'redirects' => __( 'Redirects', 'nexura-redirects' ),
			'module'    => __( 'Module', 'nexura-redirects' ),
		);
	}

	protected function get_sortable_columns() {
		return array(
			'name'   => array( 'name', false ),
			'module' => array( 'module_id', false ),
		);
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
			case 'module':
				return esc_html( $item[ $column_name ] );
			case 'redirects':
				return absint( $item[ $column_name ] );
			default:
				return '';
		}
	}

	protected function column_cb( $item ) {
		// Do not allow deleting the default group (ID 1)
		if ( $item['id'] == 1 ) {
			return '';
		}
		return sprintf(
			'<input type="checkbox" name="group_id[]" value="%d" />',
			$item['id']
		);
	}

	protected function column_name( $item ) {
		$actions = array();
		
		$actions['edit'] = sprintf( '<a href="#" class="nexura-inline-edit-group" data-id="%d">%s</a>', absint( $item['id'] ), __( 'Edit', 'nexura-redirects' ) );
		
		if ( $item['id'] != 1 ) {
			$delete_nonce = wp_create_nonce( 'nexura_delete_group' );
			$actions['delete'] = sprintf(
				'<a href="?page=%s&tab=groups&action=%s&group_id=%s&_wpnonce=%s" style="color:#b32d2e;">%s</a>',
				esc_attr( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['page'] ?? '' ) ) ),
				'delete',
				absint( $item['id'] ),
				$delete_nonce,
				__( 'Delete', 'nexura-redirects' )
			);
		}

		return sprintf( '%1$s %2$s',
			'<strong>' . esc_html( $item['name'] ) . '</strong>',
			$this->row_actions( $actions )
		);
	}

	public function single_row( $item ) {
		echo '<tr id="group-row-' . absint( $item['id'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
		$this->inline_edit_row( $item );
	}

	private function inline_edit_row( $item ) {
		echo '<tr id="edit-group-' . absint( $item['id'] ) . '" class="inline-edit-row inline-edit-row-group" style="display: none;">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td colspan="' . (int) $this->get_column_count() . '" class="colspanchange">';
		
		echo '<div class="inline-edit-wrapper" style="padding: 15px; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 2px rgba(0,0,0,.05);">';
		echo '<fieldset class="inline-edit-col-left" style="margin: 0; padding: 0;">';
		
		echo '<table class="form-table" style="margin: 0;">';
		echo '<tbody>';
		
		// Group Name
		echo '<tr>';
		echo '<th scope="row" style="width: 150px; font-weight: 600; padding: 10px 0;"><label>Name</label></th>';
		echo '<td style="padding: 10px 0;">';
		echo '<input type="text" name="edit_group_name[' . absint( $item['id'] ) . ']" value="' . esc_attr( $item['name'] ) . '" class="regular-text" style="width: 100%; max-width: none;" required>';
		echo '</td>';
		echo '</tr>';

		// Module
		echo '<tr>';
		echo '<th scope="row" style="width: 150px; font-weight: 600; padding: 10px 0;"><label>Module</label></th>';
		echo '<td style="padding: 10px 0;">';
		echo '<select name="edit_group_module[' . absint( $item['id'] ) . ']" style="width: 100%; max-width: none;">';
		$modules = array( 'wordpress' => 'WordPress', 'apache' => 'Apache', 'nginx' => 'Nginx' );
		foreach ( $modules as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '" ';
			selected( $item['module'], $val, true );
			echo '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		
		echo '<p class="submit inline-edit-save" style="margin-bottom: 0; padding-bottom: 0;">';
		echo '<button type="button" class="button cancel-inline-edit-group" data-id="' . absint( $item['id'] ) . '">Cancel</button> ';
		echo '<button type="submit" name="nexura_edit_group" value="' . absint( $item['id'] ) . '" class="button button-primary">Save</button>';
		wp_nonce_field( 'nexura_edit_group_action', 'nexura_edit_group_nonce', true, true );
		echo '</p>';
		
		echo '</fieldset>';
		echo '</div>';
		
		echo '</td>';
		echo '</tr>';
	}

	protected function column_module( $item ) {
		$module = esc_html( $item['module'] );
		return sprintf( '<span style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">%s</span>', $module );
	}

	protected function get_bulk_actions() {
		return array(
			'bulk-delete' => __( 'Delete', 'nexura-redirects' )
		);
	}

	public function prepare_items() {
		global $wpdb;
		$table_groups = $wpdb->prefix . 'nexura_redirect_groups';
		$table_redirects = $wpdb->prefix . 'nexura_redirects';
		
		$per_page = 25;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		$this->process_bulk_action();
		
		// Query data with redirects count
		$query = "SELECT g.id, g.name, g.module_id as module, (SELECT COUNT(id) FROM $table_redirects r WHERE r.group_id = g.id) as redirects FROM $table_groups g";
		$args = array();
		
		// Handle search
		if ( ! empty( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['s'] ?? '' ) ) ) ) {
			$search = sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['s'] ?? '' ) );
			$query .= " WHERE g.name LIKE %s";
			$args[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Handle sorting with strict allowlisting
		$allowed_orderbys = array( 'id', 'name', 'module' );
		$orderby_raw = sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['orderby'] ?? '' ) );
		$orderby = in_array( $orderby_raw, $allowed_orderbys, true ) ? $orderby_raw : 'id';
		
		$order_raw = strtoupper( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['order'] ?? '' ) ) );
		$order = ( 'DESC' === $order_raw ) ? 'DESC' : 'ASC';
		
		$query .= " ORDER BY {$orderby} {$order}";

		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$prepared_query = $wpdb->prepare( $query, $args );
		} else {
			$prepared_query = $query;
		}

		$total_items = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_var( "SELECT COUNT(id) FROM ($prepared_query) AS count_table" );
		
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;
		$prepared_query .= $wpdb->prepare( " LIMIT %d, %d", $offset, $per_page );
		
		$this->items = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( $prepared_query, ARRAY_A );
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	private function process_bulk_action() {
		global $wpdb;
		$table_groups = $wpdb->prefix . 'nexura_redirect_groups';

		// Single delete
		if ( 'delete' === $this->current_action() ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'nexura_delete_group' ) ) {
				die( 'Security check failed' );
			}
			$group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;
			if ( $group_id && $group_id != 1 ) {
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->delete( $table_groups, array( 'id' => $group_id ) );
			}
		}

		// Bulk delete
		if ( 'bulk-delete' === $this->current_action() ) { /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			
			$group_ids = isset( $_REQUEST['group_id'] ) ? array_map( 'absint', (array) $_REQUEST['group_id'] ) : array();
			foreach ( $group_ids as $id ) {
				if ( $id != 1 ) { // Prevent deleting default group
					/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->delete( $table_groups, array( 'id' => $id ) );
				}
			}
		}
	}
}
