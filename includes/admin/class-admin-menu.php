<?php
/**
 * Admin Menu Class.
 * Handles the registration of the admin UI pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Admin_Menu {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_init', array( $this, 'setup_wizard_redirect' ), 1 );
		add_action( 'admin_init', array( $this, 'process_setup_wizard_actions' ) );
	}

	/**
	 * Process Setup Wizard actions before headers are sent.
	 */
	public function process_setup_wizard_actions() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/class-setup-wizard.php';
		Nexura_Redirects_Setup_Wizard::process_actions();
	}

	/**
	 * Redirect to the setup wizard upon plugin activation.
	 */
	public function setup_wizard_redirect() {
		if ( get_transient( 'nexura_redirects_activation_redirect' ) ) {
			delete_transient( 'nexura_redirects_activation_redirect' );
			
			// Ensure safe redirect only if not doing AJAX and not bulk activating.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! wp_doing_ajax() && ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=nexura-redirects-setup' ) );
				exit;
			}
		}
	}

	/**
	 * Register the plugin admin menus under the Tools menu.
	 */
	public function register_menus() {
		// Top-level Menu in the Sidebar
		add_menu_page(
			__( 'Nexura Redirects & Migration', 'nexura-redirects' ),
			__( 'Redirects', 'nexura-redirects' ),
			'manage_options',
			'nexura-redirects',
			array( $this, 'render_main_page' ),
			'dashicons-randomize', // A nice redirect/arrows icon
			30 // Position in the menu
		);

		// Hidden Setup Wizard Page
		add_submenu_page(
			null, // Null parent makes it hidden from the menu
			__( 'Nexura Redirects Setup', 'nexura-redirects' ),
			__( 'Setup Wizard', 'nexura-redirects' ),
			'manage_options',
			'nexura-redirects-setup',
			array( $this, 'render_setup_wizard' )
		);
		
		// Additional subpages can be added as query arguments or tabs within the main page to keep it clean.
	}

	/**
	 * Render the main plugin dashboard.
	 */
	public function render_main_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'redirects';
		
		$tabs = array(
			'redirects' => __( 'Redirects', 'nexura-redirects' ),
			'groups'    => __( 'Groups', 'nexura-redirects' ),
			'site'      => __( 'Site', 'nexura-redirects' ),
			'log'       => __( 'Log', 'nexura-redirects' ),
			'404s'      => __( '404s', 'nexura-redirects' ),
			'import'    => __( 'Import', 'nexura-redirects' ),
			'export'    => __( 'Export', 'nexura-redirects' ),
			'options'   => __( 'Options', 'nexura-redirects' ),
			'support'   => __( 'Support', 'nexura-redirects' ),
			'security'  => __( '🛡️ Security', 'nexura-redirects' ),
		);
		?>
		<div class="wrap nexura-redirects-wrap">
			<h1><?php esc_html_e( 'Nexura Redirects & Migration', 'nexura-redirects' ); ?></h1>
			
			<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
				<?php foreach ( $tabs as $tab_slug => $tab_name ) : ?>
					<a href="?page=nexura-redirects&tab=<?php echo esc_attr( $tab_slug ); ?>" class="nav-tab <?php echo $current_tab === $tab_slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_name ); ?>
					</a>
				<?php endforeach; ?>
			</h2>
			
			<div style="display: flex; gap: 20px; align-items: flex-start;">
				<div class="nexura-redirects-content" style="flex: 1;   padding: 0;">
				<?php
				// Route tabs to specific class methods.
				switch ( $current_tab ) {
					case 'redirects':
						$this->render_redirects_tab();
						break;
					case 'groups':
						$this->render_groups_tab();
						break;
					case 'site':
						$this->render_site_tab();
						break;
					case 'log':
						$this->render_log_tab();
						break;
					case '404s':
						$this->render_404s_tab();
						break;
					case 'import':
						$this->render_import_tab();
						break;
					case 'export':
						$this->render_export_tab();
						break;
					case 'options':
						$this->render_options_tab();
						break;
					case 'support':
						$this->render_support_tab();
						break;
					case 'security':
						$this->render_security_tab();
						break;
					default:
						$this->render_redirects_tab();
						break;
				}
				?>
			</div>
			<!-- Remove extra closing div -->

				<!-- Right Sidebar for Cross Promotion -->
				<?php
				if ( ! function_exists( 'is_plugin_active' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				if ( ! is_plugin_active( 'nexura-security/nexura-security.php' ) ) : 
				?>
				<div class="nexura-redirects-sidebar" style="width: 280px; flex-shrink: 0;">
					<?php $this->render_sidebar(); ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the right-hand sidebar widget.
	 */
	private function render_sidebar() {
		?>
		<div class="nexura-security-cta">
			<img src="<?php echo esc_url( NEXURA_REDIRECTS_URL . 'assets/img/icon.png' ); ?>" alt="Nexura Shield">
			<h3><?php esc_html_e( 'Protect Your Website', 'nexura-redirects' ); ?></h3>
			<p><?php echo wp_kses_post( __( '<strong>Nexura Security</strong> is the ultimate Web Application Firewall (WAF) and malware scanner for WordPress.', 'nexura-redirects' ) ); ?></p>
			
			<ul style="list-style: none; padding: 0; margin: 0 0 25px; text-align: left; color: #cbd5e1; font-size: 14px;">
				<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-yes" style="color: #10b981;"></span> <?php esc_html_e( 'Block Brute Force Attacks', 'nexura-redirects' ); ?></li>
				<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-yes" style="color: #10b981;"></span> <?php esc_html_e( 'Real-time Malware Scanning', 'nexura-redirects' ); ?></li>
				<li style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-yes" style="color: #10b981;"></span> <?php esc_html_e( 'Login Protection & 2FA', 'nexura-redirects' ); ?></li>
			</ul>

			<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=nexura-security&tab=search&type=term' ) ); ?>" class="nexura-pulse-btn">
				<span class="nexura-pulse-dot"></span>
				<?php esc_html_e( 'Install Nexura Security - Free', 'nexura-redirects' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render the Redirects tab using WP_List_Table.
	 */
	private function render_redirects_tab() {
		global $wpdb;

		// Handle Add Redirect
		if ( isset( $_POST['nexura_add_redirect'] ) && check_admin_referer( 'nexura_add_redirect_action', 'nexura_add_redirect_nonce' ) ) {
			$source_url  = isset( $_POST['source_url'] ) ? sanitize_text_field( wp_unslash( $_POST['source_url'] ) ) : '';
			$target_url  = isset( $_POST['target_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) : '';

			// Ensure source URL is a relative path starting with /
			$source_url = wp_make_link_relative( $source_url );
			if ( ! empty( $source_url ) && strpos( $source_url, '/' ) !== 0 ) {
				$source_url = '/' . $source_url;
			}
			
			// Target URL can be external, but we make it relative if it's internal
			$target_url = wp_make_link_relative( $target_url );
			$group_id    = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 1;
			$match_type  = isset( $_POST['match_type'] ) ? sanitize_text_field( wp_unslash( $_POST['match_type'] ) ) : 'url';
			$status_code = isset( $_POST['status_code'] ) ? absint( $_POST['status_code'] ) : 301;

			if ( ! empty( $source_url ) && ! empty( $target_url ) ) {
				$table = $wpdb->prefix . 'nexura_redirects';
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->insert(
					$table,
					array(
						'old_url'     => $source_url,
						'new_url'     => $target_url,
						'status_code' => $status_code,
						'group_id'    => $group_id,
						'match_type'  => $match_type,
						'last_accessed' => current_time( 'mysql' ),
					)
				);
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Redirect added successfully.', 'nexura-redirects' ) . '</p></div>';
			}
		}
		
		// Handle Edit Redirect
		if ( isset( $_POST['nexura_edit_redirect'] ) && check_admin_referer( 'nexura_edit_redirect_action', 'nexura_edit_redirect_nonce' ) ) {
			$id = absint( $_POST['nexura_edit_redirect'] );
			
			$source_url  = isset( $_POST['edit_source_url'][$id] ) ? sanitize_text_field( wp_unslash( $_POST['edit_source_url'][$id] ) ) : '';
			$target_url  = isset( $_POST['edit_target_url'][$id] ) ? sanitize_text_field( wp_unslash( $_POST['edit_target_url'][$id] ) ) : '';

			// Ensure source URL is a relative path starting with /
			$source_url = wp_make_link_relative( $source_url );
			if ( ! empty( $source_url ) && strpos( $source_url, '/' ) !== 0 ) {
				$source_url = '/' . $source_url;
			}
			
			$target_url = wp_make_link_relative( $target_url );
			$group_id    = isset( $_POST['edit_group_id'][$id] ) ? absint( $_POST['edit_group_id'][$id] ) : 1;
			$match_type  = isset( $_POST['edit_match_type'][$id] ) ? sanitize_text_field( wp_unslash( $_POST['edit_match_type'][$id] ) ) : 'url';
			$status_code = isset( $_POST['edit_status_code'][$id] ) ? absint( $_POST['edit_status_code'][$id] ) : 301;

			if ( ! empty( $source_url ) && ! empty( $target_url ) ) {
				$table = $wpdb->prefix . 'nexura_redirects';
				/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->update(
					$table,
					array(
						'old_url'     => $source_url,
						'new_url'     => $target_url,
						'status_code' => $status_code,
						'group_id'    => $group_id,
						'match_type'  => $match_type,
					),
					array( 'id' => $id )
				);
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Redirect updated successfully.', 'nexura-redirects' ) . '</p></div>';
			}
		}

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/list-tables/class-redirects-list-table.php';

		$redirects_table = new Nexura_Redirects_List_Table();
		$redirects_table->prepare_items();
		
		// Get Groups for dropdown
		$groups = /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}nexura_redirect_groups ORDER BY name ASC" );
		?>
		
		<form method="post">
			<?php
			$redirects_table->search_box( __( 'Search Redirects', 'nexura-redirects' ), 'search_id' );
			$redirects_table->display();
			?>
		</form>

		<div class="nexura-add-group-form"  >
			<h3 style="margin-top:0;"><?php esc_html_e( 'Add redirect', 'nexura-redirects' ); ?></h3>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'nexura_add_redirect_action', 'nexura_add_redirect_nonce' ); ?>
				
				<table class="form-table" style="margin-bottom: 20px;">
					<tbody>
						<tr>
							<th scope="row" style="width: 150px; font-weight: 600;"><label for="source_url"><?php esc_html_e( 'Source URL', 'nexura-redirects' ); ?></label></th>
							<td>
								<div style="display:flex; position: relative;">
									<input type="text" name="source_url" id="source_url" class="regular-text" style="flex:1; width:100%;" placeholder="<?php esc_attr_e( 'The relative URL you want to redirect from', 'nexura-redirects' ); ?>" required>
									<button type="button" class="button" id="nexura-source-options-toggle" style="margin-left: -1px; border-top-left-radius: 0; border-bottom-left-radius: 0;"><span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 4px;"></span></button>
									
									<!-- Source URL Options Dropdown -->
									<div id="nexura-source-options" style="display:none; position:absolute; right:0; top: 100%; background: #fff; border: 1px solid #8c8f94; box-shadow: 0 3px 5px rgba(0,0,0,.1); padding: 10px; z-index: 100; min-width: 150px; margin-top: -1px;">
										<label style="display:block; margin-bottom: 8px;"><input type="checkbox" name="url_regex" value="1"> <?php esc_html_e( 'Regex', 'nexura-redirects' ); ?></label>
										<label style="display:block; margin-bottom: 8px;"><input type="checkbox" name="url_ignore_slash" value="1"> <?php esc_html_e( 'Ignore Slash', 'nexura-redirects' ); ?></label>
										<label style="display:block;"><input type="checkbox" name="url_ignore_case" value="1"> <?php esc_html_e( 'Ignore Case', 'nexura-redirects' ); ?></label>
									</div>
								</div>
							</td>
						</tr>
						
						<tr>
							<th scope="row" style="font-weight: 600;"><label for="query_params"><?php esc_html_e( 'Query Parameters', 'nexura-redirects' ); ?></label></th>
							<td>
								<select name="query_params" id="query_params" style="max-width: 300px;">
									<option value="exact"><?php esc_html_e( 'Exact match in any order', 'nexura-redirects' ); ?></option>
									<option value="ignore"><?php esc_html_e( 'Ignore all query parameters', 'nexura-redirects' ); ?></option>
									<option value="pass"><?php esc_html_e( 'Pass all query parameters to target', 'nexura-redirects' ); ?></option>
								</select>
							</td>
						</tr>

						<!-- Advanced Options (Toggled by Gear Icon) -->
						<tr class="nexura-advanced-row" style="display: none;">
							<th scope="row" style="font-weight: 600;"><label for="match_type"><?php esc_html_e( 'Match', 'nexura-redirects' ); ?></label></th>
							<td>
								<select name="match_type" id="match_type" style="max-width: 300px;">
									<option value="url"><?php esc_html_e( 'URL only', 'nexura-redirects' ); ?></option>
									<option value="url_and_login"><?php esc_html_e( 'URL and login status', 'nexura-redirects' ); ?></option>
									<option value="url_and_role"><?php esc_html_e( 'URL and role/capability', 'nexura-redirects' ); ?></option>
									<option value="url_and_referrer"><?php esc_html_e( 'URL and referrer', 'nexura-redirects' ); ?></option>
									<option value="url_and_agent"><?php esc_html_e( 'URL and user agent', 'nexura-redirects' ); ?></option>
									<option value="url_and_cookie"><?php esc_html_e( 'URL and cookie', 'nexura-redirects' ); ?></option>
									<option value="url_and_ip"><?php esc_html_e( 'URL and IP', 'nexura-redirects' ); ?></option>
									<option value="url_and_server"><?php esc_html_e( 'URL and server', 'nexura-redirects' ); ?></option>
									<option value="url_and_header"><?php esc_html_e( 'URL and HTTP header', 'nexura-redirects' ); ?></option>
								</select>
							</td>
						</tr>

						<tr class="nexura-advanced-row" style="display: none;">
							<th scope="row" style="font-weight: 600;"><label for="action_type"><?php esc_html_e( 'When matched', 'nexura-redirects' ); ?></label></th>
							<td>
								<select name="action_type" id="action_type" style="max-width: 200px; vertical-align: top;">
									<option value="redirect"><?php esc_html_e( 'Redirect to URL', 'nexura-redirects' ); ?></option>
									<option value="random"><?php esc_html_e( 'Redirect to random post', 'nexura-redirects' ); ?></option>
									<option value="pass"><?php esc_html_e( 'Pass-through', 'nexura-redirects' ); ?></option>
									<option value="404"><?php esc_html_e( 'Error (404)', 'nexura-redirects' ); ?></option>
									<option value="ignore"><?php esc_html_e( 'Do nothing (ignore)', 'nexura-redirects' ); ?></option>
								</select>
								
								<span id="nexura-http-code-wrap" style="margin-left: 10px;">
									with HTTP code 
									<select name="status_code" id="status_code" style="margin-left: 5px;">
										<option value="301">301 - Moved Permanently</option>
										<option value="302">302 - Found</option>
										<option value="303">303 - See Other</option>
										<option value="304">304 - Not Modified</option>
										<option value="307">307 - Temporary Redirect</option>
										<option value="308">308 - Permanent Redirect</option>
									</select>
								</span>
								
								<label style="margin-left: 15px;"><input type="checkbox" name="exclude_logs" value="1"> <?php esc_html_e( 'Exclude from logs', 'nexura-redirects' ); ?></label>
							</td>
						</tr>

						<tr id="nexura-target-url-row">
							<th scope="row" style="font-weight: 600;"><label for="target_url"><?php esc_html_e( 'Target URL', 'nexura-redirects' ); ?></label></th>
							<td>
								<input type="text" name="target_url" id="target_url" class="regular-text" style="width: 100%;" placeholder="<?php esc_attr_e( 'The target URL you want to redirect, or auto-complete on post name or permalink.', 'nexura-redirects' ); ?>" required>
							</td>
						</tr>

						<tr>
							<th scope="row" style="font-weight: 600;"><label for="group_id"><?php esc_html_e( 'Group', 'nexura-redirects' ); ?></label></th>
							<td>
								<select name="group_id" id="group_id" style="max-width: 300px;">
									<optgroup label="WordPress">
										<?php foreach ( $groups as $group ) : ?>
											<option value="<?php echo esc_attr( $group->id ); ?>"><?php echo esc_html( $group->name ); ?></option>
										<?php endforeach; ?>
									</optgroup>
								</select>
								<label style="margin-left: 15px;">Position <input type="number" name="position" value="0" style="width: 60px;"></label>
							</td>
						</tr>
					</tbody>
				</table>
				
				<div style="display: flex; align-items: center; gap: 10px;">
					<button type="submit" name="nexura_add_redirect" class="button button-primary"><?php esc_html_e( 'Add redirect', 'nexura-redirects' ); ?></button>
					<a href="#" id="nexura-toggle-main-advanced" style="color: #2271b1; text-decoration: none;" title="<?php esc_attr_e( 'Toggle advanced options', 'nexura-redirects' ); ?>">
						<span class="dashicons dashicons-admin-generic" style="font-size: 20px; margin-top: 4px;"></span>
					</a>
				</div>
			</form>
		</div>

		<?php
	}

	/**
	 * Render the 404s tab using WP_List_Table.
	 */
	private function render_404s_tab() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/list-tables/class-404-list-table.php';

		$table_404 = new Nexura_Redirects_404_List_Table();
		$table_404->prepare_items();
		?>
		<p><?php esc_html_e( 'Monitor broken links (404s) on your site. Create a redirect directly from here.', 'nexura-redirects' ); ?></p>
		<form method="post">
			<?php
			$table_404->search_box( __( 'Search URLs', 'nexura-redirects' ), 'search_id' );
			$table_404->display();
			?>
		</form>
		<?php
	}

	/**
	 * Render the Groups tab.
	 */
	private function render_groups_tab() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/tabs/class-groups-tab.php';
		Nexura_Redirects_Groups_Tab::render();
	}

	/**
	 * Render the Site tab.
	 */
	private function render_site_tab() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/tabs/class-site-tab.php';
		Nexura_Redirects_Site_Tab::render();
	}

	/**
	 * Render the Log tab (Success Logs).
	 */
	private function render_log_tab() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/list-tables/class-logs-list-table.php';

		$logs_table = new Nexura_Redirects_Logs_List_Table();
		$logs_table->prepare_items();
		?>
		<p><?php esc_html_e( 'View logs for successful redirects, including User Agent and IP address.', 'nexura-redirects' ); ?></p>
		<form method="post">
			<?php
			$logs_table->search_box( __( 'Search Logs', 'nexura-redirects' ), 'search_id' );
			$logs_table->display();
			?>
		</form>
		<?php
	}

	/**
	 * Render the Import tab.
	 */
	private function render_import_tab() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/tabs/class-import-export-tab.php';
		Nexura_Redirects_Import_Export_Tab::render_import();
	}

	/**
	 * Render the Export tab.
	 */
	private function render_export_tab() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/tabs/class-import-export-tab.php';
		Nexura_Redirects_Import_Export_Tab::render_export();
	}

	/**
	 * Render the Options tab.
	 */
	private function render_options_tab() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/tabs/class-options-tab.php';
		Nexura_Redirects_Options_Tab::render();
	}

	/**
	 * Render the Support tab.
	 */
	private function render_support_tab() {
		// Get site domain for WhatsApp message
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$NEXURA_site_domain = wp_parse_url( site_url(), PHP_URL_HOST );
		$NEXURA_wa_message = urlencode( "Hello, I need support for my site: " . $NEXURA_site_domain );
		$NEXURA_wa_url = "https://wa.me/8801732593040?text=" . $NEXURA_wa_message;
		// phpcs:enable
		?>
		<div style="max-width: 900px; margin: 20px auto;">
			<!-- Header Section -->
			<div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 50px 40px; border-radius: 16px; text-align: center; color: white; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); margin-bottom: 30px;">
				<h2 style="font-size: 32px; margin-top: 0; color: #fff; margin-bottom: 15px; font-weight: 700;">Features & Usage Guide</h2>
				<p style="color: #94a3b8; font-size: 17px; line-height: 1.6; max-width: 650px; margin: 0 auto;">Nexura Redirects is a powerful redirect manager for WordPress. You can easily manage 301 redirections, keep track of 404 errors, and fix loose ends.</p>
			</div>
			
			<!-- Grid Section -->
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 40px;">
				<!-- Card 1 -->
				<div style="background: #fff; padding: 35px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px -5px rgba(0, 0, 0, 0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.05)';">
					<div style="background: #eff6ff; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 25px; color: #3b82f6;">⚡</div>
					<h3 style="color: #0f172a; font-size: 22px; margin-top: 0; margin-bottom: 8px;">Simple Management</h3>
					<h4 style="color: #3b82f6; font-size: 13px; font-weight: 600; margin-top: 0; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Create and manage redirects</h4>
					<p style="color: #64748b; font-size: 15px; line-height: 1.7; margin-bottom: 0;">Go to the <strong>Redirects</strong> tab. Enter the old URL and the new target URL to instantly send visitors and search engines to the correct page without touching any server configs.</p>
				</div>
				
				<!-- Card 2 -->
				<div style="background: #fff; padding: 35px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px -5px rgba(0, 0, 0, 0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.05)';">
					<div style="background: #fef2f2; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 25px; color: #ef4444;">🚨</div>
					<h3 style="color: #0f172a; font-size: 22px; margin-top: 0; margin-bottom: 8px;">Track 404 Errors</h3>
					<h4 style="color: #ef4444; font-size: 13px; font-weight: 600; margin-top: 0; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Fix broken links directly</h4>
					<p style="color: #64748b; font-size: 15px; line-height: 1.7; margin-bottom: 0;">Keep track of all 404 errors that occur on your site in the <strong>404s</strong> tab. You can easily click "Add Redirect" directly from the log list to repair broken links and save lost traffic.</p>
				</div>

				<!-- Card 3 -->
				<div style="background: #fff; padding: 35px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px -5px rgba(0, 0, 0, 0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.05)';">
					<div style="background: #fdf4ff; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 25px; color: #d946ef;">🔄</div>
					<h3 style="color: #0f172a; font-size: 22px; margin-top: 0; margin-bottom: 8px;">Automatic Permalinks</h3>
					<h4 style="color: #d946ef; font-size: 13px; font-weight: 600; margin-top: 0; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Monitor slug changes</h4>
					<p style="color: #64748b; font-size: 15px; line-height: 1.7; margin-bottom: 0;">With "Monitor permalink changes" enabled, Nexura automatically creates a redirect whenever you change the URL of an existing post. No manual work is needed!</p>
				</div>
				
				<!-- Card 4 -->
				<div style="background: #fff; padding: 35px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px -5px rgba(0, 0, 0, 0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.05)';">
					<div style="background: #f0fdf4; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 25px; color: #22c55e;">🚀</div>
					<h3 style="color: #0f172a; font-size: 22px; margin-top: 0; margin-bottom: 8px;">Full Logging & SEO</h3>
					<h4 style="color: #22c55e; font-size: 13px; font-weight: 600; margin-top: 0; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Boost search rankings</h4>
					<p style="color: #64748b; font-size: 15px; line-height: 1.7; margin-bottom: 0;">View all redirects occurring in the <strong>Log</strong> tab. Redirecting broken links prevents indexing issues, preserves valuable link juice, and drastically improves user experience and SEO.</p>
				</div>
			</div>
		</div>

		<div style="max-width: 800px; margin: 40px auto; text-align: center; background: #fff; padding: 50px 40px; border-radius: 12px; border: 1px solid #ccd0d4; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
			<h2 style="font-size: 26px; margin-top: 0; margin-bottom: 15px; color: #1d2327;">Support the Development</h2>
			<p style="color: #50575e; font-size: 16px; max-width: 650px; margin: 0 auto 40px auto; line-height: 1.6;">
				Nexura Redirects is an open-source project dedicated to keeping WordPress fast and SEO-friendly for everyone. If this plugin has saved you time or protected your SEO rankings, please consider supporting the author!
			</p>
			
			<div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
				<!-- bKash Donate -->
				<div style="display: inline-flex; flex-direction: column; justify-content: center; background: linear-gradient(135deg, #e2136e 0%, #b80d56 100%); color: white; padding: 15px 35px; border-radius: 8px; box-shadow: 0 4px 10px rgba(226, 19, 110, 0.2);">
					<div style="font-size: 14px; font-weight: 600; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px;">☕ Donate via bKash</div>
					<div style="font-size: 22px; font-weight: 700; letter-spacing: 2px;">01732593040</div>
				</div>

				<!-- WhatsApp -->
				<a href="<?php echo esc_url( $NEXURA_wa_url ); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; padding: 15px 35px; border-radius: 8px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
					<svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
					WhatsApp Support
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Security tab.
	 */
	private function render_security_tab() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		if ( is_plugin_active( 'nexura-security/nexura-security.php' ) ) {
			?>
			<div style="width: 100%; text-align: center; background: rgba(240, 253, 244, 0.9); padding: 60px 40px; border-radius: 16px; border: 1px solid #22c55e; box-shadow: 0 10px 25px rgba(34, 197, 94, 0.15); backdrop-filter: blur(10px); box-sizing: border-box;">
				<img src="<?php echo esc_url( NEXURA_REDIRECTS_URL . 'assets/img/nexura-security.jpg' ); ?>" alt="Nexura Security Logo" style="max-width: 100px; margin-bottom: 25px; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.15);">
				<h1 style="font-size: 2.5em; margin-bottom: 15px; color: #166534; font-weight: 700; letter-spacing: -0.5px;"><?php esc_html_e( 'Your Website is Protected', 'nexura-redirects' ); ?></h1>
				<p style="font-size: 1.25em; color: #15803d; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto; line-height: 1.6;">
					<?php esc_html_e( 'Nexura Security is installed and actively monitoring your website against malware, brute force attacks, and vulnerabilities.', 'nexura-redirects' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexura' ) ); ?>" class="nexura-pulse-btn" style="font-size: 1.2em; padding: 15px 35px;">
					<span class="nexura-pulse-dot"></span>
					<?php esc_html_e( 'View Security Dashboard', 'nexura-redirects' ); ?>
				</a>
			</div>
			<?php
			return;
		}
		?>
		<div class="nexura-security-cta" style="width: 100%; text-align: center; padding: 60px 40px; box-sizing: border-box; border-radius: 16px;">
			<img src="<?php echo esc_url( NEXURA_REDIRECTS_URL . 'assets/img/nexura-security.jpg' ); ?>" alt="Nexura Security Logo" style="max-width: 120px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); margin-bottom: 25px;">
			<h1 style="font-size: 2.8em; margin-bottom: 10px; color: #fff; letter-spacing: -0.5px;"><?php esc_html_e( 'Nexura Security', 'nexura-redirects' ); ?></h1>
			<p style="font-size: 1.25em; color: #94a3b8; margin-bottom: 50px;"><?php esc_html_e( 'Enterprise-Grade Website Protection & Web Application Firewall', 'nexura-redirects' ); ?></p>
			
			<div style="display: flex; gap: 25px; text-align: left; margin-bottom: 40px; flex-wrap: wrap;">
				<div style="flex: 1; min-width: 250px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 30px; border-radius: 16px; backdrop-filter: blur(10px); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
					<h3 style="margin-top: 0; color: #60a5fa; font-size: 20px;">🔥 <?php esc_html_e( 'Web Application Firewall', 'nexura-redirects' ); ?></h3>
					<p style="margin-bottom: 0; color: #cbd5e1; line-height: 1.6;"><?php esc_html_e( 'Instantly block malicious traffic, SQL injections, and cross-site scripting (XSS) attacks before they ever reach your WordPress site.', 'nexura-redirects' ); ?></p>
				</div>
				<div style="flex: 1; min-width: 250px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 30px; border-radius: 16px; backdrop-filter: blur(10px); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
					<h3 style="margin-top: 0; color: #60a5fa; font-size: 20px;">🦠 <?php esc_html_e( 'Deep Malware Scanner', 'nexura-redirects' ); ?></h3>
					<p style="margin-bottom: 0; color: #cbd5e1; line-height: 1.6;"><?php esc_html_e( 'Automatically scan your core files, themes, and plugins to detect hidden backdoors, shells, and known malware signatures.', 'nexura-redirects' ); ?></p>
				</div>
				<div style="flex: 1; min-width: 250px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 30px; border-radius: 16px; backdrop-filter: blur(10px); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
					<h3 style="margin-top: 0; color: #60a5fa; font-size: 20px;">🔐 <?php esc_html_e( 'Two-Factor Auth (2FA)', 'nexura-redirects' ); ?></h3>
					<p style="margin-bottom: 0; color: #cbd5e1; line-height: 1.6;"><?php esc_html_e( 'Stop brute force attacks instantly. Enforce strong passwords and add an extra layer of security with built-in 2FA for administrators.', 'nexura-redirects' ); ?></p>
				</div>
			</div>

			<div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 40px; border-radius: 16px; text-align: left; margin-bottom: 40px; display: flex; gap: 40px; align-items: center; flex-wrap: wrap;">
				<div style="flex: 3; min-width: 300px;">
					<h2 style="margin-top: 0; font-size: 24px; color: #fff;"><?php esc_html_e( 'Why Your Site Needs Nexura Security', 'nexura-redirects' ); ?></h2>
					<p style="font-size: 16px; color: #94a3b8; margin-bottom: 25px; line-height: 1.6;"><?php esc_html_e( 'We built Nexura Security to be incredibly lightweight while offering premium features that other plugins charge hundreds of dollars for.', 'nexura-redirects' ); ?></p>
					
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 15px; color: #e2e8f0;">
						<div>✅ <strong><?php esc_html_e( 'Hide Login Page', 'nexura-redirects' ); ?></strong></div>
						<div>✅ <strong><?php esc_html_e( 'IP & Geo-Blocking', 'nexura-redirects' ); ?></strong></div>
						<div>✅ <strong><?php esc_html_e( 'Activity Logging', 'nexura-redirects' ); ?></strong></div>
						<div>✅ <strong><?php esc_html_e( 'File Change Detection', 'nexura-redirects' ); ?></strong></div>
						<div>✅ <strong><?php esc_html_e( 'Anti-Spam reCAPTCHA', 'nexura-redirects' ); ?></strong></div>
						<div>✅ <strong><?php esc_html_e( 'Database Protection', 'nexura-redirects' ); ?></strong></div>
					</div>
				</div>
				<div style="flex: 2; min-width: 250px; background: rgba(59, 130, 246, 0.1); padding: 40px 30px; border-radius: 16px; border: 2px dashed rgba(59, 130, 246, 0.4); text-align: center;">
					<h3 style="margin-top: 0; color: #60a5fa; font-size: 28px;"><?php esc_html_e( '100% Free', 'nexura-redirects' ); ?></h3>
					<p style="margin-bottom: 30px; color: #cbd5e1; font-size: 15px;"><?php esc_html_e( 'No locked features. No premium upsells. Just pure, uncompromised security.', 'nexura-redirects' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=nexura-security&tab=search&type=term' ) ); ?>" class="nexura-pulse-btn" style="font-size: 16px; padding: 15px 30px;">
						<span class="nexura-pulse-dot"></span>
						<?php esc_html_e( 'Install Now', 'nexura-redirects' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Setup Wizard page.
	 */
	public function render_setup_wizard() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/admin/class-setup-wizard.php';
		Nexura_Redirects_Setup_Wizard::render();
	}
}
