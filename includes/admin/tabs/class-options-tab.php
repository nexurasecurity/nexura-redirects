<?php
/**
 * Options Tab Class.
 * Handles the rendering and saving of the Master Settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Options_Tab {

	/**
	 * Render the Options tab.
	 */
	public static function render() {
		// Save settings if form is submitted
		if ( isset( $_POST['nexura_save_options'] ) && check_admin_referer( 'nexura_options_action', 'nexura_options_nonce' ) ) {
			update_option( 'nexura_redirect_log_retention', sanitize_text_field( wp_unslash( sanitize_text_field( wp_unslash( $_POST['redirect_log_retention'] ?? '' ) ) ) ) );
			update_option( 'nexura_404_log_retention', sanitize_text_field( wp_unslash( sanitize_text_field( wp_unslash( $_POST['404_log_retention'] ?? '' ) ) ) ) );
			update_option( 'nexura_ip_logging', sanitize_text_field( wp_unslash( sanitize_text_field( wp_unslash( $_POST['ip_logging'] ?? '' ) ) ) ) );
			update_option( 'nexura_url_monitor', isset( $_POST['url_monitor'] ) ? 1 : 0 );
			update_option( 'nexura_default_case_insensitive', isset( $_POST['default_case_insensitive'] ) ? 1 : 0 );
			update_option( 'nexura_default_ignore_slash', isset( $_POST['default_ignore_slash'] ) ? 1 : 0 );
			update_option( 'nexura_default_query_matching', sanitize_text_field( wp_unslash( sanitize_text_field( wp_unslash( $_POST['default_query_matching'] ?? '' ) ) ) ) );
			update_option( 'nexura_apache_htaccess_path', sanitize_text_field( wp_unslash( sanitize_text_field( wp_unslash( $_POST['apache_htaccess_path'] ?? '' ) ) ) ) );
			
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Options saved successfully.', 'nexura-redirects' ) . '</p></div>';
		}

		// Retrieve current settings
		$redirect_log_retention = get_option( 'nexura_redirect_log_retention', 'week' );
		$log_404_retention      = get_option( 'nexura_404_log_retention', 'week' );
		$ip_logging             = get_option( 'nexura_ip_logging', 'full' );
		$url_monitor            = get_option( 'nexura_url_monitor', 1 );
		$case_insensitive       = get_option( 'nexura_default_case_insensitive', 1 );
		$ignore_slash           = get_option( 'nexura_default_ignore_slash', 1 );
		$query_matching         = get_option( 'nexura_default_query_matching', 'exact' );
		$apache_htaccess_path   = get_option( 'nexura_apache_htaccess_path', '' );

		?>
		<div class="nexura-options-form">
			<form method="post" action="">
				<?php wp_nonce_field( 'nexura_options_action', 'nexura_options_nonce' ); ?>
				
				<h3><?php esc_html_e( 'Logs', 'nexura-redirects' ); ?></h3>
				<table class="nexura-options-table">
					<tr>
						<th><label for="redirect_log_retention"><?php esc_html_e( 'Redirect Logs', 'nexura-redirects' ); ?></label></th>
						<td>
							<select name="redirect_log_retention" id="redirect_log_retention">
								<option value="none" <?php selected( $redirect_log_retention, 'none' ); ?>><?php esc_html_e( 'No logs', 'nexura-redirects' ); ?></option>
								<option value="day" <?php selected( $redirect_log_retention, 'day' ); ?>><?php esc_html_e( 'A day', 'nexura-redirects' ); ?></option>
								<option value="week" <?php selected( $redirect_log_retention, 'week' ); ?>><?php esc_html_e( 'A week', 'nexura-redirects' ); ?></option>
								<option value="month" <?php selected( $redirect_log_retention, 'month' ); ?>><?php esc_html_e( 'A month', 'nexura-redirects' ); ?></option>
								<option value="forever" <?php selected( $redirect_log_retention, 'forever' ); ?>><?php esc_html_e( 'Forever', 'nexura-redirects' ); ?></option>
							</select>
							<span class="description">(time to keep logs for)</span>
						</td>
					</tr>
					<tr>
						<th><label for="404_log_retention"><?php esc_html_e( '404 Logs', 'nexura-redirects' ); ?></label></th>
						<td>
							<select name="404_log_retention" id="404_log_retention">
								<option value="none" <?php selected( $log_404_retention, 'none' ); ?>><?php esc_html_e( 'No logs', 'nexura-redirects' ); ?></option>
								<option value="day" <?php selected( $log_404_retention, 'day' ); ?>><?php esc_html_e( 'A day', 'nexura-redirects' ); ?></option>
								<option value="week" <?php selected( $log_404_retention, 'week' ); ?>><?php esc_html_e( 'A week', 'nexura-redirects' ); ?></option>
								<option value="month" <?php selected( $log_404_retention, 'month' ); ?>><?php esc_html_e( 'A month', 'nexura-redirects' ); ?></option>
								<option value="forever" <?php selected( $log_404_retention, 'forever' ); ?>><?php esc_html_e( 'Forever', 'nexura-redirects' ); ?></option>
							</select>
							<span class="description">(time to keep logs for)</span>
						</td>
					</tr>
					<tr>
						<th><label for="ip_logging"><?php esc_html_e( 'IP Logging', 'nexura-redirects' ); ?></label></th>
						<td>
							<select name="ip_logging" id="ip_logging">
								<option value="full" <?php selected( $ip_logging, 'full' ); ?>><?php esc_html_e( 'Full IP logging', 'nexura-redirects' ); ?></option>
								<option value="anonymized" <?php selected( $ip_logging, 'anonymized' ); ?>><?php esc_html_e( 'Anonymized IP (GDPR)', 'nexura-redirects' ); ?></option>
								<option value="none" <?php selected( $ip_logging, 'none' ); ?>><?php esc_html_e( 'No IP logging', 'nexura-redirects' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<h3 style="margin-top: 30px;"><?php esc_html_e( 'URL Settings', 'nexura-redirects' ); ?></h3>
				<table class="nexura-options-table">
					<tr>
						<th><label><?php esc_html_e( 'URL Monitor', 'nexura-redirects' ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" name="url_monitor" value="1" <?php checked( $url_monitor, 1 ); ?>>
								<?php esc_html_e( 'Monitor changes to posts and pages (auto-redirect when permalink changes)', 'nexura-redirects' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Default URL Settings', 'nexura-redirects' ); ?></label></th>
						<td>
							<p class="description" style="margin-top:0; margin-bottom:10px;">Applies to all redirections unless configured otherwise.</p>
							<label>
								<input type="checkbox" name="default_case_insensitive" value="1" <?php checked( $case_insensitive, 1 ); ?>>
								<?php esc_html_e( 'Case-insensitive match (e.g. /Post will match /post)', 'nexura-redirects' ); ?>
							</label><br>
							<label>
								<input type="checkbox" name="default_ignore_slash" value="1" <?php checked( $ignore_slash, 1 ); ?>>
								<?php esc_html_e( 'Ignore trailing slashes (e.g. /post/ will match /post)', 'nexura-redirects' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="default_query_matching"><?php esc_html_e( 'Default Query Matching', 'nexura-redirects' ); ?></label></th>
						<td>
							<select name="default_query_matching" id="default_query_matching">
								<option value="exact" <?php selected( $query_matching, 'exact' ); ?>><?php esc_html_e( 'Exact match in any order', 'nexura-redirects' ); ?></option>
								<option value="ignore" <?php selected( $query_matching, 'ignore' ); ?>><?php esc_html_e( 'Ignore all query parameters', 'nexura-redirects' ); ?></option>
								<option value="pass" <?php selected( $query_matching, 'pass' ); ?>><?php esc_html_e( 'Pass query parameters to target', 'nexura-redirects' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<h3 style="margin-top: 30px;"><?php esc_html_e( 'Advanced & Server', 'nexura-redirects' ); ?></h3>
				<table class="nexura-options-table">
					<tr>
						<th><label for="apache_htaccess_path"><?php esc_html_e( 'Apache .htaccess', 'nexura-redirects' ); ?></label></th>
						<td>
							<input type="text" name="apache_htaccess_path" id="apache_htaccess_path" value="<?php echo esc_attr( $apache_htaccess_path ); ?>" class="regular-text" style="width: 100%; max-width: 400px;">
							<p class="description">Redirects added to an Apache group can be saved to an .htaccess file by adding the full path here.</p>
						</td>
					</tr>
				</table>

				<p class="submit" style="margin-top: 30px;">
					<button type="submit" name="nexura_save_options" class="button button-primary"><?php esc_html_e( 'Update Options', 'nexura-redirects' ); ?></button>
				</p>
			</form>

			<div class="nexura-danger-zone">
				<h3 style="color: #dc3232; border-bottom-color: #ff00002b;"><?php esc_html_e( 'Delete Redirection', 'nexura-redirects' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Selecting this option will delete all redirections, all logs, and any options associated with the plugin. Make sure this is what you want to do.', 'nexura-redirects' ); ?></p>
				<button class="button" style="border-color: #dc3232; color: #dc3232;" onclick="if(!confirm('Are you sure you want to delete everything? This cannot be undone.')) return false;">
					<?php esc_html_e( 'Delete plugin data', 'nexura-redirects' ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}
