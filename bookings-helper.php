<?php // phpcs:disable WooCommerce.Commenting.CommentTags.AuthorTag,WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Bookings Helper
 * Version: 1.0.9
 * Plugin URI: https://github.com/woocommerce/bookings-helper
 * Description: This extension is a WooCommerce Bookings helper which helps you to troubleshoot bookings setup easier by allowing you to quickly export/import product settings.
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Text Domain: bookings-helper
 * Domain Path: /languages
 * Tested up to: 6.0.1
 * Requires at least: 5.6
 * WC tested up to: 6.3
 * WC requires at least: 6.0
 *
 * @package WordPress
 * @author WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WC_BOOKINGS_ABSPATH' ) ) {
	define( 'WC_BOOKINGS_HELPER_ABSPATH', dirname( __FILE__ ) . '/' );
}

if ( ! class_exists( 'Bookings_Helper' ) ) {

	define( 'WC_BOOKINGS_HELPER_VERSION', '1.0.9' );
	define( 'WC_BOOKINGS_HELPER_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
	define( 'WC_BOOKINGS_HELPER_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
	define( 'WC_BOOKINGS_HELPER_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'WC_BOOKINGS_HELPER_MAIN_FILE', __FILE__ );

	/**
	 * Main class.
	 *
	 * @package Bookings_Helper
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class Bookings_Helper {
		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @version 1.0.2
		 */
		public function __construct() {
			$this->includes();
			$this->init();
			
			// Declare compatibility with High-Performance Order Storage.
			add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		}

		/**
		 * Load Classes.
		 */
		public function includes() {
			require_once WC_BOOKINGS_HELPER_ABSPATH . 'includes/class-wc-bookings-helper-utils.php';
			require_once WC_BOOKINGS_HELPER_ABSPATH . 'includes/class-wc-bookings-helper-export.php';
			require_once WC_BOOKINGS_HELPER_ABSPATH . 'includes/class-wc-bookings-helper-import.php';
		}

		/**
		 * Initialize.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		}

		/**
		 * Adds submenu page to tools.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function add_submenu_page() {
			add_submenu_page( 'tools.php', 'Bookings Helper', 'Bookings Helper', 'manage_options', 'bookings-helper', array( $this, 'tool_page' ) );
		}

		/**
		 * Renders the tool page.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function tool_page() {
			include_once WC_BOOKINGS_HELPER_ABSPATH . 'templates/tool-page.php';
		}

		/**
		 * Declare compatibility with High-Performance Order Storage.
		 *
		 * @since 1.0.9
		 */
		public function declare_hpos_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_BOOKINGS_HELPER_MAIN_FILE, true );
			}
		}
	}
}

// Require the autoloader if it exists.
if ( file_exists( plugin_dir_path( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
} else {
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Please run "composer install" in the "Bookings Helper" plugin directory.', 'bookings-helper' ); ?></p>
			</div>
			<?php
		}
	);
}

/**
 * WooCommerce fallback notice.
 *
 * @since 1.0.4
 */
function woocommerce_bookings_helper_missing_wc_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Bookings Helper plugin requires WooCommerce to be installed and active. You can download %s here.', 'bookings-helper' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

/**
 * WooCommerce Bookings fallback notice.
 *
 * @since 1.0.4
 */
function woocommerce_bookings_helper_missing_bookings_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Bookings Helper plugin requires WooCommerce Bookings to be installed and active. You can download %s here.', 'bookings-helper' ), '<a href="https://woocommerce.com/products/woocommerce-bookings/" target="_blank">WooCommerce Bookings</a>' ) . '</strong></p></div>';
}

/**
 * Init function for the language directory.
 */
function woocommerce_bookings_helper_init() {
	load_plugin_textdomain( 'bookings-helper', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_bookings_helper_missing_wc_notice' );

		return;
	}

	if ( ! class_exists( 'WC_Bookings' ) ) {
		add_action( 'admin_notices', 'woocommerce_bookings_helper_missing_bookings_notice' );

		return;
	}

	new Bookings_Helper();
}

add_action( 'plugins_loaded', 'woocommerce_bookings_helper_init', 10 );

// Register WP CLI command.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'bookings-helper export-products', array( 'WC_Bookings_Helper_Products_Command', 'export' ) );
	WP_CLI::add_command( 'bookings-helper import-products', array( 'WC_Bookings_Helper_Products_Command', 'import' ) );
	WP_CLI::add_command( 'bookings-helper export-global-availability-rules', array( 'WC_Bookings_Helper_Global_Availability_Rules_Command', 'export' ) );
	WP_CLI::add_command( 'bookings-helper import-global-availability-rules', array( 'WC_Bookings_Helper_Global_Availability_Rules_Command', 'import' ) );
}

