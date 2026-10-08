<?php
/**
 * Post Editor Meta Box Integration.
 * Adds a meta box to the classic and Gutenberg editors for managing redirects.
 * Also handles auto-catching permalink changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexura_Redirects_Post_Meta_Box {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add Meta Box
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		// AJAX handler for quick add redirect
		add_action( 'wp_ajax_nexura_quick_add_redirect', array( $this, 'ajax_add_redirect' ) );

		// AJAX handler for quick delete redirect
		add_action( 'wp_ajax_nexura_quick_delete_redirect', array( $this, 'ajax_delete_redirect' ) );
	}

	/**
	 * Handle AJAX request to add a redirect from the post edit screen.
	 */
	public function ajax_add_redirect() {
		check_ajax_referer( 'nexura_save_post_redirect', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized user.', 'nexura-redirects' ) ) );
		}

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';

		if ( empty( $source ) || empty( $target ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Source or target is empty.', 'nexura-redirects' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'nexura_redirects';

		$source = wp_make_link_relative( $source );
		if ( ! empty( $source ) && strpos( $source, '/' ) !== 0 ) {
			$source = '/' . $source;
		}

		// Only make relative if internal
		$target_host = wp_parse_url( $target, PHP_URL_HOST );
		$current_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $target_host ) || $target_host === $current_host ) {
			$target = wp_make_link_relative( $target );
		}

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$inserted = $wpdb->insert(
			$table,
			array(
				'old_url'     => $source,
				'new_url'     => $target,
				'status_code' => 301,
				'group_id'    => 1,
				'match_type'  => 'url',
				'last_accessed' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( $inserted ) {
			$insert_id = $wpdb->insert_id;
			wp_send_json_success( array( 'message' => esc_html__( 'Redirect added successfully.', 'nexura-redirects' ), 'id' => $insert_id ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Database error.', 'nexura-redirects' ) ) );
		}
	}

	/**
	 * Handle AJAX request to delete a redirect from the post edit screen.
	 */
	public function ajax_delete_redirect() {
		check_ajax_referer( 'nexura_save_post_redirect', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		if ( empty( $id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid ID.' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'nexura_redirects';

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( $deleted ) {
			wp_send_json_success( array( 'message' => 'Redirect deleted successfully.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Database error or redirect not found.' ) );
		}
	}

	/**
	 * Add the meta box to posts and pages.
	 */
	public function add_meta_box() {
		$screens = array( 'post', 'page' );
		
		// Check if URL Monitor is enabled in Options
		$url_monitor_enabled = get_option( 'nexura_url_monitor', 1 );
		
		foreach ( $screens as $screen ) {
			add_meta_box(
				'nexura_redirects_meta_box',                 // Unique ID
				__( 'Nexura Redirects', 'nexura-redirects' ), // Box title
				array( $this, 'render_meta_box' ),           // Content callback
				$screen,                                     // Post type
				'normal',                                    // Context
				'low'                                        // Priority
			);
		}
	}

	/**
	 * Render the meta box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'nexura_save_post_redirect', 'nexura_post_redirect_nonce' );
		
		// Get current permalink
		$permalink = get_permalink( $post->ID );
		$relative_url = wp_make_link_relative( $permalink );

		?>
		<div class="nexura-meta-box-wrap">
			<p><?php esc_html_e( 'Instantly redirect this post to a new URL, or redirect an old URL to this post.', 'nexura-redirects' ); ?></p>
			
			<div class="nexura-meta-row">
				<label for="nexura_quick_source"><?php esc_html_e( 'Old URL (Redirect from)', 'nexura-redirects' ); ?></label>
				<input type="text" id="nexura_quick_source" name="nexura_quick_source" placeholder="/old-post-url/" value="">
			</div>
			
			<div class="nexura-meta-row">
				<label for="nexura_quick_target"><?php esc_html_e( 'Target URL (Redirect to)', 'nexura-redirects' ); ?></label>
				<input type="text" id="nexura_quick_target" name="nexura_quick_target" value="<?php echo esc_attr( $relative_url ); ?>" readonly style="background:#f0f0f1;">
				<p class="description"><?php esc_html_e( 'By default, we redirect to this post. You can change this in the main dashboard.', 'nexura-redirects' ); ?></p>
			</div>
			
			<button type="button" class="button button-primary nexura-quick-add-btn" id="nexura-quick-add-btn">
				<?php esc_html_e( 'Add Redirect', 'nexura-redirects' ); ?>
			</button>

			<div class="nexura-existing-redirects">
				<strong><?php esc_html_e( 'Redirects pointing to this post:', 'nexura-redirects' ); ?></strong>
				<ul style="margin-top: 8px; color: #646970;">
					<?php
					global $wpdb;
					$table = $wpdb->prefix . 'nexura_redirects';
					/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
					$redirects = $wpdb->get_results( $wpdb->prepare( "SELECT id, old_url, new_url FROM {$table} WHERE new_url = %s", $relative_url ) );
					
					if ( ! empty( $redirects ) ) {
						foreach ( $redirects as $redirect ) {
							echo '<li style="margin-bottom: 5px;">';
							echo '<span style="color:#d63638;">' . esc_html( $redirect->old_url ) . '</span> &rarr; <span style="color:#00a32a;">' . esc_html( $redirect->new_url ) . '</span>';
							echo ' <a href="#" class="nexura-delete-redirect" data-id="' . esc_attr( $redirect->id ) . '" style="color:#b32d2e; text-decoration:none; margin-left:10px; font-size:12px;">[Remove]</a>';
							echo '</li>';
						}
					} else {
						echo '<li class="nexura-no-redirects">' . esc_html__( 'No redirects found for this post yet.', 'nexura-redirects' ) . '</li>';
					}
					?>
				</ul>
			</div>
		</div>

		<?php
	}
}
