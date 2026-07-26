<?php
/**
 * Import and Export Tabs UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Import_Export_Tab {

	/**
	 * Render the Import tab.
	 */
	public static function render_import() {
		?>
		<div class="nexura-options-form">
			<h3><?php esc_html_e( 'Import', 'nexura-redirects' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Here you can import redirects from CSV, JSON, or Apache .htaccess files. If you are importing from another plugin, their export file may also work.', 'nexura-redirects' ); ?></p>
			
			<div style="display: flex; gap: 40px; flex-wrap: wrap; margin-top: 30px;">
				<!-- File Upload Section -->
				<div style="flex: 1; min-width: 300px;">
					<h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><?php esc_html_e( 'Upload File', 'nexura-redirects' ); ?></h4>
					<form method="post" enctype="multipart/form-data" action="">
						<?php wp_nonce_field( 'nexura_import_file', 'nexura_import_nonce' ); ?>
						<p>
							<input type="file" name="nexura_import_file" accept=".csv,.json,.htaccess">
						</p>
						<p>
							<label for="import_group"><?php esc_html_e( 'Import to group:', 'nexura-redirects' ); ?></label>
							<select name="import_group" id="import_group">
								<option value="1">Redirections</option>
								<option value="new">-- Create New Group --</option>
							</select>
						</p>
						<p class="submit">
							<button type="submit" name="nexura_import_file_submit" class="button button-primary"><?php esc_html_e( 'Upload', 'nexura-redirects' ); ?></button>
						</p>
					</form>
				</div>

				<!-- Copy/Paste Section -->
				<div style="flex: 1; min-width: 300px;">
					<h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><?php esc_html_e( 'Copy & Paste', 'nexura-redirects' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Paste your CSV or .htaccess rules below:', 'nexura-redirects' ); ?></p>
					<form method="post" action="">
						<?php wp_nonce_field( 'nexura_import_text', 'nexura_import_nonce' ); ?>
						<p>
							<textarea name="nexura_import_text" rows="6" style="width: 100%; font-family: monospace;"></textarea>
						</p>
						<p>
							<label for="import_text_group"><?php esc_html_e( 'Import to group:', 'nexura-redirects' ); ?></label>
							<select name="import_text_group" id="import_text_group">
								<option value="1">Redirections</option>
							</select>
						</p>
						<p class="submit">
							<button type="submit" name="nexura_import_text_submit" class="button"><?php esc_html_e( 'Import Text', 'nexura-redirects' ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Export tab.
	 */
	public static function render_export() {
		?>
		<div class="nexura-options-form">
			<h3><?php esc_html_e( 'Export', 'nexura-redirects' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Export your redirects and logs. You can export everything, or just a specific group.', 'nexura-redirects' ); ?></p>
			
			<form method="post" action="">
			<table class="nexura-options-table" style="margin-top: 30px;">
				<tr>
					<th><label for="export_module"><?php esc_html_e( 'Export to:', 'nexura-redirects' ); ?></label></th>
					<td>
						<select id="export_module" name="export_module">
							<option value="csv">CSV (Comma Separated Values)</option>
							<option value="json">JSON</option>
							<option value="apache">Apache .htaccess</option>
							<option value="nginx">Nginx rewrite rules</option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="export_group"><?php esc_html_e( 'Group:', 'nexura-redirects' ); ?></label></th>
					<td>
						<select id="export_group" name="export_group">
							<option value="all">All Groups</option>
							<option value="1">Redirections</option>
						</select>
					</td>
				</tr>
			</table>

			<p class="submit" style="margin-top: 20px;">
				<button type="submit" name="nexura_export_redirects" class="button button-primary"><?php esc_html_e( 'Export Redirects', 'nexura-redirects' ); ?></button>
				<button type="submit" name="nexura_export_logs" class="button"><?php esc_html_e( 'Export 404 Logs', 'nexura-redirects' ); ?></button>
			</p>
			</form>
		</div>
		<?php
	}
}
