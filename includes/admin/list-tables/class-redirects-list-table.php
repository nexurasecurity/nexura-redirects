<?php
/**
 * Redirects List Table.
 * Uses WP_List_Table to display redirects in a native UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Nexura_Redirects_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Redirect', 'nexura-redirects' ),
			'plural'   => __( 'Redirects', 'nexura-redirects' ),
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'old_url'     => __( 'Old URL', 'nexura-redirects' ),
			'new_url'     => __( 'New URL', 'nexura-redirects' ),
			'status_code' => __( 'Code', 'nexura-redirects' ),
			'hits'        => __( 'Hits', 'nexura-redirects' ),
			'last_accessed' => __( 'Last Accessed', 'nexura-redirects' ),
		);
		return $columns;
	}

	protected function get_sortable_columns() {
		$sortable_columns = array(
			'old_url'     => array( 'old_url', false ),
			'new_url'     => array( 'new_url', false ),
			'hits'        => array( 'hits', false ),
			'status_code' => array( 'status_code', false ),
		);
		return $sortable_columns;
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'new_url':
			case 'status_code':
			case 'hits':
			case 'last_accessed':
				return esc_html( $item[ $column_name ] );
			default:
				return '';
		}
	}



	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['id']
		);
	}

	protected function column_old_url( $item ) {
		$delete_nonce = wp_create_nonce( 'nexura_delete_redirect' );
		$title = '<strong>' . esc_html( $item['old_url'] ) . '</strong>';
		
		$actions = array(
			'edit'   => sprintf( '<a href="#" class="nexura-inline-edit" data-id="%d">Edit</a>', absint( $item['id'] ) ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&redirect=%s&_wpnonce=%s">Delete</a>', esc_attr( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['page'] ?? '' ) ) ), 'delete', absint( $item['id'] ), $delete_nonce ),
		);

		return $title . $this->row_actions( $actions );
	}

	public function single_row( $item ) {
		echo '<tr id="redirect-row-' . absint( $item['id'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
		$this->inline_edit_row( $item );
	}

	private function inline_edit_row( $item ) {
		global $wpdb;
		$groups = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}nexura_redirect_groups ORDER BY name ASC" );
		
		echo '<tr id="edit-redirect-' . absint( $item['id'] ) . '" class="inline-edit-row inline-edit-row-redirect" style="display: none;">';
		echo '<td colspan="' . $this->get_column_count() . '" class="colspanchange">';
		
		echo '<div class="inline-edit-wrapper" style="padding: 15px; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 2px rgba(0,0,0,.05);">';
		echo '<fieldset class="inline-edit-col-left" style="margin: 0; padding: 0;">';
		
		echo '<table class="form-table" style="margin: 0;">';
		echo '<tbody>';
		
		// Source URL
		echo '<tr>';
		echo '<th scope="row" style="width: 150px; font-weight: 600; padding: 10px 0;"><label>Source URL</label></th>';
		echo '<td style="padding: 10px 0;">';
		echo '<input type="text" name="edit_source_url[' . absint( $item['id'] ) . ']" value="' . esc_attr( $item['old_url'] ) . '" class="regular-text" style="width: 100%; max-width: none;">';
		echo '</td>';
		echo '</tr>';

		// Match Type / Query Params (Simplified for inline edit)
		echo '<tr>';
		echo '<th scope="row" style="width: 150px; font-weight: 600; padding: 10px 0;"><label>Match Type</label></th>';
		echo '<td style="padding: 10px 0;">';
		echo '<select name="edit_match_type[' . absint( $item['id'] ) . ']" style="width: 100%; max-width: none;">';
		$match_types = array(
			'url' => 'URL only',
			'url_and_login' => 'URL and login status',
			'url_and_role' => 'URL and role/capability',
			'url_and_referrer' => 'URL and referrer',
			'url_and_agent' => 'URL and user agent',
			'url_and_cookie' => 'URL and cookie',
			'url_and_ip' => 'URL and IP',
			'url_and_server' => 'URL and server',
			'url_and_header' => 'URL and HTTP header'
		);
		foreach ( $match_types as $val => $label ) {
			$selected = selected( $item['match_type'], $val, false );
			echo '<option value="' . esc_attr( $val ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Target URL
		echo '<tr>';
		echo '<th scope="row" style="font-weight: 600; padding: 10px 0;"><label>Target URL</label></th>';
		echo '<td style="padding: 10px 0;">';
		echo '<input type="text" name="edit_target_url[' . absint( $item['id'] ) . ']" value="' . esc_attr( $item['new_url'] ) . '" class="regular-text" style="width: 100%; max-width: none;">';
		echo '</td>';
		echo '</tr>';

		// Group
		echo '<tr>';
		echo '<th scope="row" style="font-weight: 600; padding: 10px 0;"><label>Group</label></th>';
		echo '<td style="padding: 10px 0;">';
		echo '<select name="edit_group_id[' . absint( $item['id'] ) . ']" style="width: 100%; max-width: none;">';
		foreach ( $groups as $group ) {
			$selected = selected( $item['group_id'], $group->id, false );
			echo '<option value="' . esc_attr( $group->id ) . '" ' . $selected . '>' . esc_html( $group->name ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';
		
		// Status Code
		echo '<tr>';
		echo '<th scope="row" style="font-weight: 600; padding: 10px 0;"><label>HTTP Code</label></th>';
		echo '<td style="padding: 10px 0;">';
		echo '<select name="edit_status_code[' . absint( $item['id'] ) . ']" style="width: 100%; max-width: none;">';
		$codes = array( 301 => '301 - Moved Permanently', 302 => '302 - Found', 303 => '303 - See Other', 304 => '304 - Not Modified', 307 => '307 - Temporary Redirect', 308 => '308 - Permanent Redirect' );
		foreach ( $codes as $code => $label ) {
			$selected = selected( $item['status_code'], $code, false );
			echo '<option value="' . esc_attr( $code ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		
		echo '<p class="submit inline-edit-save" style="margin-bottom: 0; padding-bottom: 0;">';
		echo '<button type="button" class="button cancel-inline-edit" data-id="' . absint( $item['id'] ) . '">Cancel</button> ';
		echo '<button type="submit" name="nexura_edit_redirect" value="' . absint( $item['id'] ) . '" class="button button-primary">Save</button>';
		echo wp_nonce_field( 'nexura_edit_redirect_action', 'nexura_edit_redirect_nonce', true, false );
		echo '</p>';
		
		echo '</fieldset>';
		echo '</div>';
		
		echo '</td>';
		echo '</tr>';
	}

	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => 'Delete',
		);
		return $actions;
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nexura_redirects';

		$per_page = 20;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Process bulk action
		$this->process_bulk_action();

		// Fetch Data
		$query = "SELECT * FROM $table_name";
		$args = array();
		
		// Search
		if ( ! empty( sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['s'] ?? '' ) ) ) ) {
			$search = sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_REQUEST['s'] ?? '' ) );
			$query .= " WHERE old_url LIKE %s OR new_url LIKE %s";
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$args[] = $like;
			$args[] = $like;
		}

		// Sorting
		$orderby = ( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['orderby'] ) ) : 'id';
		$order   = ( isset( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ $_GET['order'] ) ) : 'DESC';
		
		// Validating orderby and order
		$valid_columns = array( 'old_url', 'new_url', 'hits', 'status_code', 'id' );
		$orderby = in_array( $orderby, $valid_columns, true ) ? $orderby : 'id';
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		$query .= " ORDER BY $orderby $order";

		if ( ! empty( $args ) ) {
			$prepared_query = $wpdb->prepare( $query, $args );
		} else {
			$prepared_query = $query;
		}

		// Pagination
		$current_page = $this->get_pagenum();
		$total_items  = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_var( "SELECT COUNT(id) FROM ($prepared_query) AS count_table" );
		
		$offset = ( $current_page - 1 ) * $per_page;
		$prepared_query .= /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );

		$this->items = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( $prepared_query, ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	/**
	 * Process bulk actions (like delete)
	 */
	private function process_bulk_action() {
		$action = $this->current_action();

		if ( 'delete' === $action || 'bulk-delete' === $action ) {
			
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
			
			// Verify nonce (it could be a single delete or bulk delete)
			if ( 'delete' === $action && ! wp_verify_nonce( $nonce, 'nexura_delete_redirect' ) ) {
				return; // Invalid nonce for single delete
			}
			
			if ( 'bulk-delete' === $action && ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				return; // Invalid nonce for bulk delete
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'nexura_redirects';

			// Bulk delete uses 'bulk-delete' array, single delete uses 'redirect' query arg
			$redirect_ids = array();
			if ( isset( $_REQUEST['bulk-delete'] ) ) {
				$redirect_ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['bulk-delete'] ) );
			} elseif ( isset( $_REQUEST['redirect'] ) ) {
				$redirect_ids = array( absint( wp_unslash( $_REQUEST['redirect'] ) ) );
			}

			if ( is_array( $redirect_ids ) ) {
				$redirect_ids = array_map( 'absint', $redirect_ids );
				if ( ! empty( $redirect_ids ) ) {
					$placeholders = implode( ', ', array_fill( 0, count( $redirect_ids ), '%d' ) );
					/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
					$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id IN ($placeholders)", $redirect_ids ) );
				}
			}
		}
	}
}
