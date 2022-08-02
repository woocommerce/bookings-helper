<?php
/**
 * Plugin Name: Bookings Helper
 * Version: 1.0.3
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

	define( 'WC_BOOKINGS_HELPER_VERSION', '1.0.4' );
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
		}

		/**
		 * Load Classes.
		 */
		public function includes() {
			require_once WC_BOOKINGS_HELPER_ABSPATH . 'includes/wc-bookings-helper-functions.php';
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
			$ziparchive_available = class_exists( 'ZipArchive' );
			$file_label           = $ziparchive_available ? 'ZIP' : 'JSON';
			?>
            <div class="wrap">
                <h1>Bookings Helper</h1>
                <hr/>
                <div>
                    <h3>Global Availability Rules</h3>
					<?php
					$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
					?>
                    <form action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                        <table>
                            <tr>
                                <td>
                                    <input type="submit" class="button" value="Export Rules"/> <label>Exports all global availability rules.</label>
                                    <input type="hidden" name="action" value="export_globals"/>
									<?php wp_nonce_field( 'export_globals' ); ?>
                                </td>
                            </tr>
                        </table>
                    </form>

					<?php
					$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
					?>
                    <form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                        <table>
                            <tr>
                                <td>
                                    <label>Choose a file (<?php echo $file_label; ?>).</label><input type="file" name="import"/>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <input type="submit" class="button" value="Import Rules"/> <label>Imports global availability rules replacing your current rules.</label>
                                    <input type="hidden" name="action" value="import_globals"/>
									<?php wp_nonce_field( 'import_globals' ); ?>
                                </td>
                            </tr>
                        </table>
                    </form>

                    <h3>Booking Products</h3>
					<?php
					$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
					?>
                    <form action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                        <table>
                            <tr>
                                <td>
                                    <label>Product ID: <input type="number" name="product_id" min="1"/></label>
                                    <input type="submit" class="button" value="Export Booking Product"/> <label>Exports a specific Booking product and its settings including resources.</label>
                                    <input type="hidden" name="action" value="export_product"/>
									<?php wp_nonce_field( 'export_product' ); ?>
                                </td>
                            </tr>
                        </table>
                    </form>

					<?php
					$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
					?>
                    <form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                        <table>
                            <tr>
                                <td>
                                    <label>Choose a file (<?php echo $file_label; ?>).</label><input type="file" name="import"/>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <input type="submit" class="button" value="Import Product"/> <label>Imports a booking product.</label>
                                    <input type="hidden" name="action" value="import_product"/>
									<?php wp_nonce_field( 'import_product' ); ?>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
			<?php

			if ( ! $ziparchive_available ) {
				echo '<div><p><strong style="color:red;">PHP ZipArchive extension is not installed. Import/Export will be in JSON format.</strong></p></div>';
			}
		}

	}

	new Bookings_Helper();
}

add_action( 'plugins_loaded', 'woocommerce_bookings_helper_init', 10 );

function woocommerce_bookings_helper_init() {
	load_plugin_textdomain( 'bookings-helper', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_bookings_missing_wc_notice' );

		return;
	}

	$GLOBALS['wc_bookings'] = WC_Bookings::instance();

}