<?php
/**
 * Recommended Plugins Class.
 * Handles the WordPress.org compliant cross-promotion.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Recommended_Plugins {

	/**
	 * Render the Recommended Plugins tab content.
	 */
	public static function render() {
		?>
		<div class="nexura-recommended-wrapper" style="max-width: 800px; margin-top: 20px;">
			
			<div class="postbox" style="padding: 20px;">
				<div style="display: flex; align-items: center; margin-bottom: 15px;">
					<span class="dashicons dashicons-shield" style="font-size: 64px; width: 64px; height: 64px; color: #2271b1; margin-right: 15px;"></span>
					<div>
						<h3 style="margin: 0; font-size: 18px;">Nexura Security - Ultimate WordPress Firewall & Malware Scanner</h3>
						<p style="margin: 5px 0 0; color: #666;">By Nexura Security</p>
					</div>
				</div>
				
				<p>
					<?php esc_html_e( 'Secure your website from hackers, brute force attacks, and malicious bots. Nexura Security provides Enterprise-grade protection completely for free. No database bloat, no slowdowns.', 'nexura-redirects' ); ?>
				</p>
				
				<ul style="list-style-type: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Pre-Boot Web Application Firewall (WAF)', 'nexura-redirects' ); ?></li>
					<li><?php esc_html_e( 'Deep Malware & Backdoor Scanner', 'nexura-redirects' ); ?></li>
					<li><?php esc_html_e( 'Two-Factor Authentication (2FA)', 'nexura-redirects' ); ?></li>
					<li><?php esc_html_e( 'Zero Database Bloat & Lightning Fast', 'nexura-redirects' ); ?></li>
				</ul>
				
				<div style="margin-top: 20px;">
					<?php
					$plugin_slug = 'nexura-security';
					$plugin_file = 'nexura-security/nexura-security.php';
					
					if ( is_plugin_active( $plugin_file ) ) {
						echo '<span class="button button-disabled">' . esc_html__( 'Active', 'nexura-redirects' ) . '</span>';
					} else {
						$install_url = wp_nonce_url(
							self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ),
							'install-plugin_' . $plugin_slug
						);
						echo '<a href="' . esc_url( $install_url ) . '" class="button button-primary">' . esc_html__( 'Install Nexura Security for Free', 'nexura-redirects' ) . '</a>';
					}
					?>
					<a href="https://wordpress.org/plugins/nexura-security/" target="_blank" class="button button-secondary" style="margin-left: 10px;">
						<?php esc_html_e( 'View Details', 'nexura-redirects' ); ?>
					</a>
				</div>
			</div>
			
		</div>
		<?php
	}
}
