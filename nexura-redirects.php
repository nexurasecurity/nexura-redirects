<?php
/**
 * Plugin Name:       Nexura Redirects – 301 Redirects, 404 Monitor & DB Migration
 * Plugin URI:        https://wordpress.org/plugins/nexura-redirects/
 * Description:       Database-safe Migration + Serialized Search & Replace + Auto Redirect + 404 Recovery in one ultra-fast, native WordPress plugin.
 * Version:           1.1.2
 * Author:            Nexura Security
 * Author URI:        https://profiles.wordpress.org/nexurasecurity/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nexura-redirects
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'NEXURA_REDIRECTS_VERSION', '1.1.2' );
define( 'NEXURA_REDIRECTS_FILE', __FILE__ );
define( 'NEXURA_REDIRECTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'NEXURA_REDIRECTS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Nexura_Redirects Class.
 */
final class Nexura_Redirects {

	/**
	 * Single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Main Nexura_Redirects Instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Includes.
		$this->includes();

		// Add Plugin Action Links.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {
		require_once NEXURA_REDIRECTS_DIR . 'includes/class-redirect-engine.php';
		require_once NEXURA_REDIRECTS_DIR . 'includes/class-logger.php';
		require_once NEXURA_REDIRECTS_DIR . 'includes/class-auto-redirect.php';
		require_once NEXURA_REDIRECTS_DIR . 'includes/class-import-export.php';
		require_once NEXURA_REDIRECTS_DIR . 'includes/class-search-replace.php';

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
			require_once NEXURA_REDIRECTS_DIR . 'includes/admin/class-admin-menu.php';
			require_once NEXURA_REDIRECTS_DIR . 'includes/admin/class-post-meta-box.php';
			
			new Nexura_Redirects_Admin_Menu();
			new Nexura_Redirects_Post_Meta_Box();
		}

		if ( ! is_admin() || wp_doing_ajax() ) {
			new Nexura_Redirects_Engine();
			new Nexura_Redirects_Logger();
		}
		
		new Nexura_Redirects_Auto();
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		register_activation_hook( NEXURA_REDIRECTS_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( NEXURA_REDIRECTS_FILE, array( $this, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( $this, 'check_version_and_upgrade' ) );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		// Handled automatically by WordPress 4.6+.
	}

	/**
	 * Automatically upgrade database tables and migrate options for existing users.
	 */
	public function check_version_and_upgrade() {
		$installed_version = get_option( 'nexura_redirects_version', '1.0.0' );

		if ( version_compare( $installed_version, NEXURA_REDIRECTS_VERSION, '<' ) ) {
			// 1. Upgrade database tables safely via dbDelta (never loses data)
			require_once NEXURA_REDIRECTS_DIR . 'includes/class-db-schema.php';
			Nexura_Redirects_DB_Schema::create_tables();

			// 2. Ensure daily log cleanup cron job is scheduled for existing users
			if ( ! wp_next_scheduled( 'nexura_redirects_daily_cleanup' ) ) {
				wp_schedule_event( time(), 'daily', 'nexura_redirects_daily_cleanup' );
			}

			// 3. Migrate legacy options if present from v1.1.0/v1.1.1
			$legacy_auto = get_option( 'nexura_redirects_enable_auto' );
			if ( false !== $legacy_auto && false === get_option( 'nexura_url_monitor', false ) ) {
				update_option( 'nexura_url_monitor', $legacy_auto ? 1 : 0 );
			}

			$legacy_404 = get_option( 'nexura_redirects_enable_404' );
			if ( false !== $legacy_404 && false === get_option( 'nexura_404_log_retention', false ) ) {
				update_option( 'nexura_404_log_retention', $legacy_404 ? 'week' : 'none' );
				update_option( 'nexura_redirect_log_retention', $legacy_404 ? 'week' : 'none' );
			}

			$legacy_ip = get_option( 'nexura_redirects_store_ip' );
			if ( false !== $legacy_ip && false === get_option( 'nexura_ip_logging', false ) ) {
				update_option( 'nexura_ip_logging', $legacy_ip ? 'full' : 'none' );
			}

			// 4. Update stored version
			update_option( 'nexura_redirects_version', NEXURA_REDIRECTS_VERSION );
		}
	}

	/**
	 * Runs on plugin activation.
	 */
	public function activate() {
		// Setup custom tables here.
		require_once NEXURA_REDIRECTS_DIR . 'includes/class-db-schema.php';
		Nexura_Redirects_DB_Schema::create_tables();

		// Schedule daily log cleanup if not already scheduled
		if ( ! wp_next_scheduled( 'nexura_redirects_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'nexura_redirects_daily_cleanup' );
		}

		// Store installed version
		update_option( 'nexura_redirects_version', NEXURA_REDIRECTS_VERSION );

		// Set transient to trigger setup wizard redirect (new installs only)
		set_transient( 'nexura_redirects_activation_redirect', true, 30 );
	}

	/**
	 * Runs on plugin deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'nexura_redirects_daily_cleanup' );
	}

	/**
	 * Enqueue admin styles and scripts only on plugin pages and post editors.
	 *
	 * @param string $hook_suffix The current admin screen hook suffix.
	 */
	public function enqueue_admin_styles( $hook_suffix ) {
		$is_plugin_page = ( strpos( $hook_suffix, 'nexura-redirects' ) !== false );
		$is_post_edit   = in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true );

		if ( ! $is_plugin_page && ! $is_post_edit ) {
			return;
		}

		wp_enqueue_style( 'nexura-redirects-admin', NEXURA_REDIRECTS_URL . 'assets/css/admin.css', array(), NEXURA_REDIRECTS_VERSION );
		wp_enqueue_script( 'nexura-redirects-admin-js', NEXURA_REDIRECTS_URL . 'assets/js/admin.js', array( 'jquery' ), NEXURA_REDIRECTS_VERSION, true );
	}

	/**
	 * Add custom action links on the plugins page.
	 * 
	 * @param array $links The existing action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=nexura-redirects' ) . '">' . __( 'Settings', 'nexura-redirects' ) . '</a>';
		$security_link = '<a href="' . admin_url( 'plugin-install.php?s=nexura-security&tab=search&type=term' ) . '" style="color: #2271b1; font-weight: bold;">' . __( 'Get Nexura Security', 'nexura-redirects' ) . '</a>';
		
		array_unshift( $links, $settings_link );
		array_push( $links, $security_link );
		
		return $links;
	}
}

/**
 * Initialize the plugin.
 */
function nexura_redirects() {
	return Nexura_Redirects::get_instance();
}

// Start the plugin.
nexura_redirects();
