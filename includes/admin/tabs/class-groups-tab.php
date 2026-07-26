<?php
/**
 * Groups Tab Class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Groups_Tab {

	public static function render() {
		// Handle Add Group
		if ( isset( $_POST['nexura_add_group'] ) && check_admin_referer( 'nexura_add_group_action', 'nexura_add_group_nonce' ) ) {
			$name = isset( $_POST['group_name'] ) ? sanitize_text_field( wp_unslash( $_POST['group_name'] ) ) : '';
			$module = isset( $_POST['group_module'] ) ? sanitize_text_field( wp_unslash( $_POST['group_module'] ) ) : 'wordpress';

			if ( ! empty( $name ) ) {
				global $wpdb;
				$table = $wpdb->prefix . 'nexura_redirect_groups';
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->insert(
					$table,
					array(
						'name'      => $name,
						'module_id' => $module,
					)
				);
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Group added successfully.', 'nexura-redirects' ) . '</p></div>';
			}
		}

		// Handle Edit Group
		if ( isset( $_POST['nexura_edit_group'] ) && check_admin_referer( 'nexura_edit_group_action', 'nexura_edit_group_nonce' ) ) {
			$id = absint( $_POST['nexura_edit_group'] );
			$name = isset( $_POST['edit_group_name'][$id] ) ? sanitize_text_field( wp_unslash( $_POST['edit_group_name'][$id] ) ) : '';
			$module = isset( $_POST['edit_group_module'][$id] ) ? sanitize_text_field( wp_unslash( $_POST['edit_group_module'][$id] ) ) : 'wordpress';

			if ( ! empty( $name ) ) {
				global $wpdb;
				$table = $wpdb->prefix . 'nexura_redirect_groups';
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->update(
					$table,
					array(
						'name'      => $name,
						'module_id' => $module,
					),
					array( 'id' => $id )
				);
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Group updated successfully.', 'nexura-redirects' ) . '</p></div>';
			}
		}

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/list-tables/class-groups-list-table.php';

		$groups_table = new Nexura_Redirects_Groups_List_Table();
		$groups_table->prepare_items();
		?>
		
		<form method="post">
			<?php
			$groups_table->search_box( __( 'Search Groups', 'nexura-redirects' ), 'search_id' );
			$groups_table->display();
			?>
		</form>

		<div class="nexura-add-group-form">
			<h3><?php esc_html_e( 'Add Group', 'nexura-redirects' ); ?></h3>
			<p class="description" style="margin-bottom: 15px;"><?php esc_html_e( 'Use groups to organise your redirects. Groups are assigned to a module, which affects how the redirects in that group work. If you are unsure then stick to the WordPress module.', 'nexura-redirects' ); ?></p>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'nexura_add_group_action', 'nexura_add_group_nonce' ); ?>
				<div class="nexura-form-inline">
					<label for="group_name"><?php esc_html_e( 'Name', 'nexura-redirects' ); ?></label>
					<input type="text" name="group_name" id="group_name" class="regular-text" required>
					
					<select name="group_module" id="group_module">
						<option value="wordpress">WordPress</option>
						<option value="apache">Apache</option>
						<option value="nginx">Nginx</option>
					</select>

					<button type="submit" name="nexura_add_group" class="button button-primary"><?php esc_html_e( 'Add', 'nexura-redirects' ); ?></button>
				</div>
			</form>
		</div>

		<?php
	}
}
