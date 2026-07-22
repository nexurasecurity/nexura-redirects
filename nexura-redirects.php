<?php
/**
 * Plugin Name:       Nexura Redirects
 * Plugin URI:        https://wordpress.org/plugins/nexura-redirects/
 * Description:       Database-safe Migration + Serialized Search & Replace + Auto Redirect + 404 Recovery in one ultra-fast, native WordPress plugin.
 * Version:           1.1.0
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
define( 'NEXURA_REDIRECTS_VERSION', '1.1.0' );
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
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		// Removed load_plugin_textdomain as per WP.org standards (WP 4.6+ handles this automatically).
	}

	/**
	 * Runs on plugin activation.
	 */
	public function activate() {
		// Setup custom tables here.
		require_once NEXURA_REDIRECTS_DIR . 'includes/class-db-schema.php';
		Nexura_Redirects_DB_Schema::create_tables();

		// Set transient to trigger setup wizard redirect
		set_transient( 'nexura_redirects_activation_redirect', true, 30 );
	}
	/**
	 * Enqueue admin styles.
	 */
	public function enqueue_admin_styles() {
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
