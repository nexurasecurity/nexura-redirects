<?php
/**
 * Setup Wizard Class.
 * Handles the multi-step onboarding screen after plugin activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Setup_Wizard {

	/**
	 * Process form actions for the Setup Wizard.
	 * Hooked to admin_init to ensure redirects work before headers are sent.
	 */
	public static function process_actions() {
		// Only run on the setup wizard page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'nexura-redirects-setup' ) {
			return;
		}

		$step = isset( $_GET['step'] ) ? intval( wp_unslash( $_GET['step'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Handle form submission for Step 1
		$nonce = isset( $_POST['nexura_setup_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nexura_setup_nonce'] ) ) : '';
		if ( $step === 1 && isset( $_POST['nexura_setup_step_1'] ) && wp_verify_nonce( $nonce, 'nexura_setup_action' ) ) {
			
			$enable_404 = isset( $_POST['enable_404_logging'] ) ? 1 : 0;
			$enable_auto = isset( $_POST['enable_auto_redirect'] ) ? 1 : 0;
			$store_ip = isset( $_POST['store_ip_info'] ) ? 1 : 0;
			
			update_option( 'nexura_redirects_enable_404', $enable_404 );
			update_option( 'nexura_redirects_enable_auto', $enable_auto );
			update_option( 'nexura_redirects_store_ip', $store_ip );
			
			// Process Auto-Install Nexura Security
			if ( isset( $_POST['install_nexura_security'] ) ) {
				$plugin_file = 'nexura-security/nexura-security.php';
				
				if ( ! is_plugin_active( $plugin_file ) ) {
					if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
						// Install it
						if ( ! function_exists( 'plugins_api' ) ) {
							require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
						}
						if ( ! class_exists( 'Plugin_Upgrader' ) ) {
							require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
						}
						
						$api = plugins_api( 'plugin_information', array(
							'slug'   => 'nexura-security',
							'fields' => array( 'sections' => false )
						) );
						
						if ( ! is_wp_error( $api ) ) {
							$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
							$installed = $upgrader->install( $api->download_link );
							
							if ( $installed === true ) {
								activate_plugin( $plugin_file );
								delete_transient( 'fs_plugin_nexura-security_activated' );
							}
						}
					} else {
						// Already downloaded, just activate
						activate_plugin( $plugin_file );
						delete_transient( 'fs_plugin_nexura-security_activated' );
					}
				}
			}
			
			// Move to Step 2
			wp_safe_redirect( admin_url( 'admin.php?page=nexura-redirects-setup&step=2' ) );
			exit;
		}

		// Handle skip to finish
		if ( $step === 4 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=nexura-redirects' ) );
			exit;
		}
	}

	/**
	 * Render the Setup Wizard UI.
	 */
	public static function render() {
		$step = isset( $_GET['step'] ) ? intval( wp_unslash( $_GET['step'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		
		?>
		<div class="nexura-wizard-wrap">
			<div class="nexura-wizard-header">
				<h1>Nexura Redirects 🚀</h1>
				<p>Setup Wizard</p>
			</div>

			<div class="nexura-wizard-content">
				<?php if ( $step === 1 ) : ?>
					<h2>Basic Setup</h2>
					<p>These are some options you may want to enable now. They can be changed at any time.</p>
					
					<form method="post" action="">
						<?php wp_nonce_field( 'nexura_setup_action', 'nexura_setup_nonce' ); ?>
						
						<div class="nexura-setting-row">
							<input type="checkbox" name="enable_auto_redirect" id="enable_auto_redirect" value="1" checked>
							<div class="nexura-setting-info">
								<h3>Monitor permalink changes in WordPress posts and pages.</h3>
								<p>If you change the permalink in a post or page then Nexura can automatically create a redirect for you.</p>
							</div>
						</div>

						<div class="nexura-setting-row">
							<input type="checkbox" name="enable_404_logging" id="enable_404_logging" value="1" checked>
							<div class="nexura-setting-info">
								<h3>Keep a log of all redirects and 404 errors.</h3>
								<p>Storing logs for redirects and 404s will allow you to see what is happening on your site. This will increase your database storage requirements.</p>
							</div>
						</div>

						<div class="nexura-setting-row" style="background: #fff; border:none; padding-left:0;">
							<input type="checkbox" name="store_ip_info" id="store_ip_info" value="1">
							<div class="nexura-setting-info">
								<h3 style="color:#6b7280; font-weight:normal;">Store IP information for redirects and 404 errors.</h3>
								<p>Storing the IP address allows you to perform additional log actions. Note that you will need to adhere to local laws regarding the collection of data (for example GDPR).</p>
							</div>
						</div>

						<?php
						if ( ! function_exists( 'is_plugin_active' ) ) {
							require_once ABSPATH . 'wp-admin/includes/plugin.php';
						}
						$is_security_active = is_plugin_active( 'nexura-security/nexura-security.php' );
						?>
						<div class="nexura-setting-row" style="background: <?php echo $is_security_active ? '#f0fdf4' : 'linear-gradient(135deg, #0f172a, #1e293b)'; ?>; border: 1px solid <?php echo $is_security_active ? '#22c55e' : '#334155'; ?>; padding: 24px; border-radius: 12px; margin-top: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
							<input type="checkbox" name="install_nexura_security" id="install_nexura_security" value="1" checked <?php disabled( $is_security_active ); ?> style="<?php echo $is_security_active ? '' : 'border-color: #475569; background: #334155;'; ?>">
							<div class="nexura-setting-info">
								<h3 style="color: <?php echo $is_security_active ? '#166534' : '#fff'; ?>; font-weight: 600; margin-top: 0; display: flex; align-items: center; font-size: 18px;">
									<img src="<?php echo esc_url( NEXURA_REDIRECTS_URL . 'assets/img/icon.png' ); ?>" alt="" style="width: 28px; height: 28px; margin-right: 12px; vertical-align: middle;">
									<?php echo $is_security_active ? esc_html__( 'Nexura Security Installed', 'nexura-redirects' ) : esc_html__( 'Install Nexura Security (Recommended)', 'nexura-redirects' ); ?>
								</h3>
								<?php if ( $is_security_active ) : ?>
									<p style="margin-bottom: 0; color: #15803d; font-weight: 500;"><?php esc_html_e( '✅ Your website is completely safe and protected by Nexura Security!', 'nexura-redirects' ); ?></p>
								<?php else : ?>
									<p style="margin-bottom: 0; color: #94a3b8; font-size: 14px;"><?php esc_html_e( 'Automatically install and activate our free Web Application Firewall (WAF) to protect your site against malware, SQL injections, and brute force attacks.', 'nexura-redirects' ); ?></p>
								<?php endif; ?>
							</div>
						</div>

						<div class="nexura-wizard-footer">
							<button type="submit" name="nexura_setup_step_1" class="nexura-btn-primary">Continue</button>
						</div>
					</form>

				<?php elseif ( $step === 2 ) : ?>
					<h2>Database & Environment Check</h2>
					<p>Nexura uses highly optimized custom database tables to communicate with WordPress. We are checking your environment now.</p>
					
					<div class="nexura-api-box">
						<p style="font-size: 16px; font-weight: 600; margin: 0 0 15px 0;">Database Tables: <span class="nexura-badge" id="db-status-badge">Checking - 0%</span></p>
						<div id="db-check-info" style="color: #64748b; font-size: 14px;">
							Running tests on wp_nexura_redirects, wp_nexura_redirect_groups...
						</div>
					</div>

					<div class="nexura-wizard-footer">
						<a href="<?php echo esc_url( admin_url('admin.php?page=nexura-redirects-setup&step=1') ); ?>" class="nexura-btn-secondary">Go back</a>
						<a href="<?php echo esc_url( admin_url('admin.php?page=nexura-redirects-setup&step=3') ); ?>" class="nexura-btn-primary" id="btn-continue-step-2" style="display:none;">Continue</a>
					</div>

				<?php elseif ( $step === 3 ) : ?>
					<h2 style="text-align:center;">Setting up Nexura Redirects</h2>
					<p style="text-align:center;">Please remain on this page until complete.</p>
					
					<p style="font-weight: 600;" id="progress-percent">Progress: 0%</p>
					<div class="nexura-progress-container">
						<div class="nexura-progress-bar" id="progress-bar"></div>
					</div>
					<p class="nexura-status-text" id="status-text">Initializing Setup...</p>

					<div class="nexura-wizard-footer" style="justify-content: center; margin-top: 40px;">
						<a href="<?php echo esc_url( admin_url('admin.php?page=nexura-redirects') ); ?>" class="nexura-btn-primary" id="btn-finish-setup" style="display:none;">Finish Setup</a>
					</div>

				<?php endif; ?>

				<div style="text-align: center; margin-top: 40px;">
					<a href="#" style="color: #64748b; text-decoration: underline; font-size: 13px;">I need support!</a>
				</div>
			</div>
		</div>
		<?php
	}
}
