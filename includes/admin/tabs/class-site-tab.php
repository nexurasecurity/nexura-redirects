<?php
/**
 * Site Tab Class.
 * Handles Relocate Site, Site Aliases, Canonical Settings, and HTTP Headers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Site_Tab {

	public static function render() {
		// Handle Save Settings
		if ( isset( $_POST['nexura_save_site'] ) && check_admin_referer( 'nexura_site_action', 'nexura_site_nonce' ) ) {
			update_option( 'nexura_site_relocate', sanitize_text_field( wp_unslash( sanitize_text_field( wp_unslash( $_POST['relocate_domain'] ?? '' ) ) ) ) );
			update_option( 'nexura_site_force_https', isset( $_POST['force_https'] ) ? 1 : 0 );
			update_option( 'nexura_site_preferred_domain', sanitize_text_field( wp_unslash( sanitize_text_field( wp_unslash( $_POST['preferred_domain'] ?? '' ) ) ) ) );
			
			// Handle Aliases
			if ( isset( $_POST['site_aliases'] ) && is_array( $_POST['site_aliases'] ) ) {
				$aliases = array_map( 'sanitize_text_field', wp_unslash( $_POST['site_aliases'] ) );
				// Remove empty aliases
				$aliases = array_filter( $aliases );
				update_option( 'nexura_site_aliases', $aliases );
			} else {
				update_option( 'nexura_site_aliases', array() );
			}

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Site settings saved successfully.', 'nexura-redirects' ) . '</p></div>';
		}

		// Retrieve current settings
		$relocate_domain  = get_option( 'nexura_site_relocate', '' );
		$force_https      = get_option( 'nexura_site_force_https', 0 );
		$preferred_domain = get_option( 'nexura_site_preferred_domain', 'none' );
		$aliases          = get_option( 'nexura_site_aliases', array() );

		?>
		<div class="nexura-options-form">
			<form method="post" action="">
				<?php wp_nonce_field( 'nexura_site_action', 'nexura_site_nonce' ); ?>
				
				<div class="notice notice-warning inline" style="margin: 0 0 20px 0;">
					<p><?php esc_html_e( 'Options on this page can cause problems if used incorrectly. You can temporarily disable them to make changes if needed.', 'nexura-redirects' ); ?></p>
				</div>

				<!-- Relocate Site -->
				<h3><?php esc_html_e( 'Relocate Site', 'nexura-redirects' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Want to redirect the entire site? Enter a domain to redirect everything, except WordPress login and admin. Enabling this option will disable any site aliases or canonical settings.', 'nexura-redirects' ); ?></p>
				<table class="nexura-options-table" style="margin-bottom: 30px;">
					<tr>
						<th style="width: 200px;"><label for="relocate_domain"><?php esc_html_e( 'Relocate to domain:', 'nexura-redirects' ); ?></label></th>
						<td>
							<input type="url" name="relocate_domain" id="relocate_domain" value="<?php echo esc_url( $relocate_domain ); ?>" class="regular-text" placeholder="https://example.com">
						</td>
					</tr>
				</table>

				<!-- Site Aliases -->
				<h3><?php esc_html_e( 'Site Aliases', 'nexura-redirects' ); ?></h3>
				<p class="description"><?php esc_html_e( 'A site alias is another domain that you want to be redirected to this site. For example, an old domain, or a subdomain. This will redirect all URLs, including WordPress login and admin.', 'nexura-redirects' ); ?></p>
				<table class="wp-list-table widefat fixed striped table-view-list" style="margin-bottom: 10px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Aliased Domain', 'nexura-redirects' ); ?></th>
							<th style="width: 100px;"><?php esc_html_e( 'Action', 'nexura-redirects' ); ?></th>
						</tr>
					</thead>
					<tbody id="nexura-aliases-list">
						<?php if ( empty( $aliases ) ) : ?>
							<tr class="no-items"><td class="colspanchange" colspan="2"><?php esc_html_e( 'No aliases defined.', 'nexura-redirects' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $aliases as $index => $alias ) : ?>
								<tr>
									<td>
										<input type="text" name="site_aliases[]" value="<?php echo esc_attr( $alias ); ?>" class="regular-text" style="width: 100%;" placeholder="e.g. old-domain.com">
									</td>
									<td>
										<button type="button" class="button nexura-remove-alias"><?php esc_html_e( 'Remove', 'nexura-redirects' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				<button type="button" class="button" id="nexura-add-alias"><?php esc_html_e( 'Add Alias', 'nexura-redirects' ); ?></button>
				
				<!-- Canonical Settings -->
				<h3 style="margin-top: 40px;"><?php esc_html_e( 'Canonical Settings', 'nexura-redirects' ); ?></h3>
				<table class="nexura-options-table" style="margin-bottom: 30px;">
					<tr>
						<td colspan="2" style="padding-top: 0;">
							<label>
								<input type="checkbox" name="force_https" value="1" <?php checked( $force_https, 1 ); ?>>
								<strong><?php esc_html_e( 'Force a redirect from HTTP to HTTPS', 'nexura-redirects' ); ?></strong>
								<span style="color:#646970; margin-left: 10px;">http://domain.com ⇒ https://domain.com</span>
							</label>
						</td>
					</tr>
					<tr>
						<th style="padding-top: 20px; width: 200px;"><?php esc_html_e( 'Preferred domain:', 'nexura-redirects' ); ?></th>
						<td style="padding-top: 20px;">
							<label style="display:block; margin-bottom: 10px;">
								<input type="radio" name="preferred_domain" value="none" <?php checked( $preferred_domain, 'none' ); ?>>
								<?php esc_html_e( 'Don\'t set a preferred domain', 'nexura-redirects' ); ?>
							</label>
							<label style="display:block; margin-bottom: 10px;">
								<input type="radio" name="preferred_domain" value="remove_www" <?php checked( $preferred_domain, 'remove_www' ); ?>>
								<?php esc_html_e( 'Remove www from domain', 'nexura-redirects' ); ?>
								<span style="color:#646970; margin-left: 10px;">http://www.domain.com ⇒ http://domain.com</span>
							</label>
							<label style="display:block; margin-bottom: 10px;">
								<input type="radio" name="preferred_domain" value="add_www" <?php checked( $preferred_domain, 'add_www' ); ?>>
								<?php esc_html_e( 'Add www to domain', 'nexura-redirects' ); ?>
								<span style="color:#646970; margin-left: 10px;">http://domain.com ⇒ http://www.domain.com</span>
							</label>
						</td>
					</tr>
				</table>

				<!-- HTTP Headers -->
				<h3 style="margin-top: 40px;"><?php esc_html_e( 'HTTP Headers', 'nexura-redirects' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Site headers are added across your site, including redirects. (Coming soon)', 'nexura-redirects' ); ?></p>
				<table class="wp-list-table widefat fixed striped table-view-list" style="margin-bottom: 10px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Location', 'nexura-redirects' ); ?></th>
							<th><?php esc_html_e( 'Header', 'nexura-redirects' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr class="no-items"><td class="colspanchange" colspan="2"><?php esc_html_e( 'No headers defined.', 'nexura-redirects' ); ?></td></tr>
					</tbody>
				</table>

				<p class="submit" style="margin-top: 40px;">
					<button type="submit" name="nexura_save_site" class="button button-primary"><?php esc_html_e( 'Update Settings', 'nexura-redirects' ); ?></button>
				</p>
			</form>
		</div>


		<?php
	}
}
