<?php
/**
 * Migration & Search Replace Tab UI.
 * Handles database migration and serialized string replacement.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Migration_Tab {

	/**
	 * Render the Migration / Search & Replace tab.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		// Handle Search & Replace execution
		if ( isset( $_POST['nexura_run_migration'] ) && check_admin_referer( 'nexura_migration_action', 'nexura_migration_nonce' ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Unauthorized user.', 'nexura-redirects' ) );
			}

			$search  = isset( $_POST['search_string'] ) ? sanitize_text_field( wp_unslash( $_POST['search_string'] ) ) : '';
			$replace = isset( $_POST['replace_string'] ) ? sanitize_text_field( wp_unslash( $_POST['replace_string'] ) ) : '';
			$tables  = isset( $_POST['selected_tables'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['selected_tables'] ) ) : array();
			$dry_run = ! empty( $_POST['dry_run'] );

			if ( empty( $search ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Search string cannot be empty.', 'nexura-redirects' ) . '</p></div>';
			} elseif ( empty( $tables ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please select at least one database table.', 'nexura-redirects' ) . '</p></div>';
			} else {
				require_once NEXURA_REDIRECTS_DIR . 'includes/class-search-replace.php';
				$result = Nexura_Redirects_Search_Replace::run( $search, $replace, $tables, $dry_run );

				$status_title = $dry_run ? esc_html__( 'Dry Run Completed (No changes made)', 'nexura-redirects' ) : esc_html__( 'Search & Replace Completed Successfully!', 'nexura-redirects' );
				?>
				<div class="notice <?php echo $dry_run ? 'notice-warning' : 'notice-success'; ?> is-dismissible" style="padding: 15px 20px;">
					<h3 style="margin-top:0;"><?php echo esc_html( $status_title ); ?></h3>
					<ul style="list-style: disc; margin-left: 20px;">
						<li><strong><?php esc_html_e( 'Tables Processed:', 'nexura-redirects' ); ?></strong> <?php echo esc_html( $result['tables'] ); ?></li>
						<li><strong><?php esc_html_e( 'Rows Checked:', 'nexura-redirects' ); ?></strong> <?php echo esc_html( number_format_i18n( $result['rows_checked'] ) ); ?></li>
						<li><strong><?php esc_html_e( 'Rows Changed / Matched:', 'nexura-redirects' ); ?></strong> <?php echo esc_html( number_format_i18n( $result['rows_changed'] ) ); ?></li>
					</ul>
					<?php if ( $dry_run ) : ?>
						<p style="margin-bottom:0; color:#b45309;"><em><?php esc_html_e( 'To apply these changes permanently to your database, uncheck "Dry Run" and click Run again.', 'nexura-redirects' ); ?></em></p>
					<?php endif; ?>
				</div>
				<?php
			}
		}

		// Retrieve all WordPress tables
		$all_tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		?>
		<div class="nexura-options-form">
			<h3><?php esc_html_e( 'Database Migration & Serialized Search Replace', 'nexura-redirects' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Safely migrate your WordPress database or replace domain names. Handles complex PHP serialized data for Elementor, Divi, theme options, and widgets without corrupting data.', 'nexura-redirects' ); ?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( 'nexura_migration_action', 'nexura_migration_nonce' ); ?>

				<table class="form-table" style="margin-top: 20px;">
					<tbody>
						<tr>
							<th scope="row"><label for="search_string"><?php esc_html_e( 'Search For (Old String / Domain)', 'nexura-redirects' ); ?></label></th>
							<td>
								<input type="text" name="search_string" id="search_string" class="regular-text" style="width: 100%; max-width: 500px;" placeholder="https://old-domain.com" required>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="replace_string"><?php esc_html_e( 'Replace With (New String / Domain)', 'nexura-redirects' ); ?></label></th>
							<td>
								<input type="text" name="replace_string" id="replace_string" class="regular-text" style="width: 100%; max-width: 500px;" placeholder="https://new-domain.com">
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Select Tables', 'nexura-redirects' ); ?></th>
							<td>
								<div style="margin-bottom: 8px;">
									<button type="button" class="button button-small" onclick="document.querySelectorAll('.nexura-table-cb').forEach(el => el.checked = true);"><?php esc_html_e( 'Select All', 'nexura-redirects' ); ?></button>
									<button type="button" class="button button-small" onclick="document.querySelectorAll('.nexura-table-cb').forEach(el => el.checked = false);"><?php esc_html_e( 'Deselect All', 'nexura-redirects' ); ?></button>
								</div>
								<div style="max-height: 180px; overflow-y: auto; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px;">
									<?php foreach ( $all_tables as $table_name ) : ?>
										<label style="display: flex; align-items: center; gap: 6px; font-size: 13px;">
											<input type="checkbox" name="selected_tables[]" value="<?php echo esc_attr( $table_name ); ?>" class="nexura-table-cb" checked>
											<?php echo esc_html( $table_name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Dry Run', 'nexura-redirects' ); ?></th>
							<td>
								<label style="font-weight: 600;">
									<input type="checkbox" name="dry_run" value="1" checked>
									<?php esc_html_e( 'Dry Run (Test only - report matches without modifying database)', 'nexura-redirects' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Highly recommended to test first. Always backup your database before performing live migration.', 'nexura-redirects' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit" style="margin-top: 25px;">
					<button type="submit" name="nexura_run_migration" class="button button-primary">
						<?php esc_html_e( 'Run Search & Replace', 'nexura-redirects' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
