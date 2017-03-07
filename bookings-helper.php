<?php
/**
 * Plugin Name: Bookings Helper
 * Version: 1.0.1
 * Plugin URI: https://github.com/woocommerce/bookings-helper
 * Description: This extension is a WooCommerce Bookings helper which helps you to troubleshoot bookings setup easier by allowing you to quickly export/import product settings.
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Requires at least: 4.7.0
 * Tested up to: 4.7.0
 *
 * @package WordPress
 * @author WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bookings_Helper' ) ) {
	/**
	 * Main class.
	 *
	 * @package Bookings_Helper
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class Bookings_Helper {
		public $notice;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Initialize.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'init', array( $this, 'catch_requests' ), 20 );
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
			if ( ! empty( $this->notice ) ) {
				echo $this->notice;
			}
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
			<div class="wrap">
				<h1>Bookings Helper</h1>
				<hr />
				<div>
					<h3>Global Availability Rules</h3>
					<form action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
						<table>
							<tr>
								<td>
									<input type="submit" class="button" value="Export Rules" /> <label>Exports all global availability rules.</label>
									<input type="hidden" name="action" value="export_globals" />
									<?php wp_nonce_field( 'export_globals' ); ?>
								</td>
							</tr>
						</table>
					</form>

					<form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
						<table>
							<tr>
								<td>
									<label>Choose a file (JSON).</label><input type="file" name="import" />
								</td>
							</tr>

							<tr>
								<td>
									<input type="submit" class="button" value="Import Rules" /> <label>Imports global availability rules replacing your current rules.</label>
									<input type="hidden" name="action" value="import_globals" />
									<?php wp_nonce_field( 'import_globals' ); ?>
								</td>
							</tr>
						</table>
					</form>

					<h3>Booking Products</h3>
					<form action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
						<table>
							<tr>
								<td>
									<label>Product ID: <input type="number" name="product_id" min="1" /></label>
									<input type="submit" class="button" value="Export Booking Product" /> <label>Exports a specific Booking product and its settings including resources.</label>
									<input type="hidden" name="action" value="export_product" />
									<?php wp_nonce_field( 'export_product' ); ?>
								</td>
							</tr>
						</table>
					</form>

					<form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
						<table>
							<tr>
								<td>
									<label>Choose a file (JSON).</label><input type="file" name="import" />
								</td>
							</tr>

							<tr>
								<td>
									<input type="submit" class="button" value="Import Product" /> <label>Imports a booking product.</label>
									<input type="hidden" name="action" value="import_product" />
									<?php wp_nonce_field( 'import_product' ); ?>
								</td>
							</tr>
						</table>
					</form>

					<h3>Resources Relationships Clean Up</h3>
					<p>Bookings 1.10 beta had a bug that created extra entries relating products and resources, this will clean up the extra entries.</p>
					<p><strong>It is recommended you do a database backup before running this.</strong>
					<form action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
						<table>
							<tr>
								<td>
									<input type="submit" class="button" value="Clean Up" /> <label>Removes extra database entries for product resource relationships.</label>
									<input type="hidden" name="action" value="clean_up" />
									<?php wp_nonce_field( 'clean_up' ); ?>
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
			<?php
		}

		/**
		 * Catches form requests.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function catch_requests() {
			if ( ! isset( $_GET['page'] ) || 'bookings-helper' !== $_GET['page'] ) {
				return;
			}

			if ( ! isset( $_POST['action'] ) || ! isset( $_POST['_wpnonce'] ) ) {
				return;
			}

			if (
				'export_globals' !== $_POST['action'] &&
				'import_globals' !== $_POST['action'] &&
				'export_product' !== $_POST['action'] &&
				'import_product' !== $_POST['action'] &&
				'clean_up'       !== $_POST['action']
			) {
				return;
			}

			if (
				! wp_verify_nonce( $_POST['_wpnonce'], 'export_globals' ) &&
				! wp_verify_nonce( $_POST['_wpnonce'], 'import_globals' ) &&
				! wp_verify_nonce( $_POST['_wpnonce'], 'export_product' ) &&
				! wp_verify_nonce( $_POST['_wpnonce'], 'import_product' ) &&
				! wp_verify_nonce( $_POST['_wpnonce'], 'clean_up' )
			) {
				wp_die( 'Cheatin&#8217; huh?' );
			}

			switch ( $_POST['action'] ) {
				case 'export_globals':
					$this->export_global_rules();
					break;
				
				case 'import_globals':
					$this->import_global_rules();
					break;
				
				case 'export_product':
					$this->export_product();
					break;
				
				case 'import_product':
					$this->import_product();
					break;

				case 'clean_up':
					$this->clean_up();
					break;
			}
		}

		/**
		 * Triggers the download feature of the browser.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 * @param string $data
		 * @param string $prefix
		 */
		public function trigger_download( $data = '', $prefix = '' ) {
			if ( empty( $data ) ) {
				return;
			}

			@set_time_limit(0);

			// Disable GZIP
			if ( function_exists( 'apache_setenv' ) ) {
				@apache_setenv( 'no-gzip', 1 );
			}

			@ini_set( 'zlib.output_compression', 'Off' );
			@ini_set( 'output_buffering', 'Off' );
			@ini_set( 'output_handler', '' );

			$filename_prefix = $prefix;

			$filename = sprintf( '%1$s-%2$s.json', $filename_prefix, date( 'Y-m-d', current_time( 'timestamp' ) ) );

			header( 'Content-Type: application/json; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			file_put_contents( 'php://output', $data );

			exit;
		}

		/**
		 * Exports global availability rules file for browser download.
		 *
		 * @since 1.0.0
		 * @param 1.0.0
		 */
		public function export_global_rules() {
			try {
				$global_rules = get_option( 'wc_global_booking_availability', array() );

				if ( empty( $global_rules ) ) {
					throw new Exception( 'There are no rules to export.' );					
				}

				$global_rules_json = json_encode( $global_rules );

				$this->trigger_download( $global_rules_json, 'bookings-global-rules' );
			} catch ( Exception $e ) {
				$this->print_notice( $e->getMessage() );

				return;
			}
		}

		/**
		 * Imports global availability rules from file.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function import_global_rules() {
			try {
				if ( empty( $_FILES ) || empty( $_FILES['import'] ) || 0 !== $_FILES['import']['error'] || empty( $_FILES['import']['tmp_name'] ) ) {
					throw new Exception( 'There are no rules to import or file is not valid.' );
				} else {
					if ( $_FILES['import']['size'] > 1000000 ) {
						throw new Exception( 'The file exceeds 1MB.' );
					}

					$global_rules_json = file_get_contents( $_FILES['import']['tmp_name'] );

					if ( ! $this->is_json( $global_rules_json ) ) {
						throw new Exception( 'The file is not in a valid JSON format.' );
					}
				}

				$global_rules = json_decode( $global_rules_json, true );

				update_option( 'wc_global_booking_availability', $global_rules );

				$this->print_notice( 'Global Availability Rules imported successfully!', 'success' );

				return;
			} catch ( Exception $e ) {
				$this->print_notice( $e->getMessage() );

				return;
			}
		}

		/**
		 * Exports a specific product by ID.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function export_product() {
			try {
				$product_id     = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : '';
				$product_status = get_post_status( $product_id );
				
				if ( empty( $product_id ) || empty( $product_status ) ) {
					throw new Exception( 'This booking product does not exist!' );
				}

				global $wpdb;

				// Products.
				$product = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = 'product' AND ID = %d", $product_id ), ARRAY_A );

				if ( empty( $product ) ) {
					throw new Exception( 'This booking product does not exist!' );
				}

				// Product metas.
				$product_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND ( meta_key LIKE '%%wc_booking%%' OR meta_key = '_resource_base_costs' OR meta_key = '_resource_block_costs' OR meta_key = '_wc_display_cost' OR meta_key = '_virtual' )", $product_id ), ARRAY_A );

				if ( empty( $product_meta ) ) {
					throw new Exception( 'This booking product does not exist!' );
				}

				// Booking relationships ( resources ).
				$resources = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d", $product_id ), ARRAY_A );

				$prepared_resources = array();
				$prepared_persons   = array();

				// If resources exists, we need to extract the meta
				// information for each resource.
				if ( ! empty( $resources ) ) {
					foreach ( $resources as $key => $value ) {
						$resource = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = 'bookable_resource' AND ID = %d", $value['resource_id'] ), ARRAY_A );

						if ( ! empty( $resource ) ) {
							$resource_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND ( meta_key = 'qty' OR meta_key = '_wc_booking_availability' )", $value['resource_id'] ), ARRAY_A );
						}

						$prepared_resources[] = array( 'resource' => $resource[0], 'resource_meta' => $resource_meta );
					}
				}

				// Persons.
				$persons = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_excerpt FROM {$wpdb->posts} WHERE post_type = 'bookable_person' AND post_parent = %d", $product_id ), ARRAY_A );

				if ( ! empty( $persons ) ) {
					foreach ( $persons as $person ) {
						$person_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $person['ID'] ), ARRAY_A );

						$prepared_persons[] = array( 'person' => $person, 'person_meta' => $person_meta );
					}
				}

				$prepared_json = json_encode( array( 
					'product'      => $product[0], 
					'product_meta' => $product_meta,
					'resources'    => $prepared_resources,
					'persons'      => $prepared_persons,
				) );

				$this->trigger_download( $prepared_json, 'booking-product-' . $product_id );
			} catch ( Exception $e ) {
				$this->print_notice( $e->getMessage() );

				return;
			}
		}

		/**
		 * Imports booking product from file.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function import_product() {
			try {
				if ( empty( $_FILES ) || empty( $_FILES['import'] ) || 0 !== $_FILES['import']['error'] || empty( $_FILES['import']['tmp_name'] ) ) {
					throw new Exception( 'There is no bookable product to import or file is not valid.' );
				} else {
					if ( $_FILES['import']['size'] > 1000000 ) {
						throw new Exception( 'The file exceeds 1MB.' );
					}

					$product_json = file_get_contents( $_FILES['import']['tmp_name'] );

					if ( ! $this->is_json( $product_json ) ) {
						throw new Exception( 'The file is not in a valid JSON format.' );
					}
				}

				$product = json_decode( $product_json, true );

				global $wpdb;

				// Product.
				$product_data = array( 
					'post_title'  => sanitize_text_field( $product['product']['post_title'] ) . ' (bookings test #' . absint( $product['product']['ID'] ) . ')',
					'post_type'   => 'product',
					'post_status' => 'publish',
				);

				$product_id = wp_insert_post( $product_data, false );

				if ( empty( $product_id ) ) {
					throw new Exception( 'Failed to create product.' );
				}

				// Product meta.
				foreach ( $product['product_meta'] as $meta ) {
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $product_id, sanitize_text_field( $meta['meta_key'] ), sanitize_text_field( $meta['meta_value'] ) ) );
				}

				wp_set_object_terms( $product_id, 'booking', 'product_type' );

				// Resources.
				if ( ! empty( $product['resources'] ) ) {
					$resource_base_costs      = get_post_meta( $product_id, '_resource_base_costs', true );
					$new_resource_base_costs  = array();
					$resource_block_costs     = get_post_meta( $product_id, '_resource_block_costs', true );
					$new_resource_block_costs = array();
					$i                        = 0;

					foreach ( $product['resources'] as $resource ) {
						$resource_data = array( 
							'post_title'  => sanitize_text_field( $resource['resource']['post_title'] ) . ' (resource test #' . absint( $resource['resource']['ID'] ) . ')',
							'post_type'   => 'bookable_resource',
							'post_status' => 'publish',
						);

						$resource_id = wp_insert_post( $resource_data, false );

						if ( empty( $resource_id ) ) {
							throw new Exception( 'Failed to create resource.' );
						}

						foreach ( $resource['resource_meta'] as $meta ) {
							$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $resource_id, sanitize_text_field( $meta['meta_key'] ), sanitize_text_field( $meta['meta_value'] ) ) );		
						}

						$new_resource_base_costs[ $resource_id ]  = array_values( $resource_base_costs )[ $i ];
						$new_resource_block_costs[ $resource_id ] = array_values( $resource_block_costs )[ $i ];

						$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wc_booking_relationships ( product_id, resource_id ) VALUES ( %d, %d )", $product_id, $resource_id ) );

						$i++;
					}

					if ( ! empty( $new_resource_base_costs ) ) {
						update_post_meta( $product_id, '_resource_base_costs', $new_resource_base_costs );
					}

					if ( ! empty( $new_resource_block_costs ) ) {
						update_post_meta( $product_id, '_resource_block_costs', $new_resource_block_costs );
					}
				}

				// Persons.
				if ( ! empty( $product['persons'] ) ) {
					foreach ( $product['persons'] as $person ) {
						$person_data = array( 
							'post_title'  => sanitize_text_field( $person['person']['post_title'] ) . ' (person test #' . absint( $person['person']['ID'] ) . ')',
							'post_type'   => 'bookable_person',
							'post_status' => 'publish',
							'post_parent' => absint( $product_id ),
							'post_excerpt' => sanitize_text_field( $person['person']['post_excerpt'] ),
						);

						$person_id = wp_insert_post( $person_data, false );

						if ( empty( $person_id ) ) {
							throw new Exception( 'Failed to create person.' );
						}

						foreach ( $person['person_meta'] as $meta ) {
							$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", absint( $person_id ), sanitize_text_field( $meta['meta_key'] ), sanitize_text_field( $meta['meta_value'] ) ) );		
						}
					}
				}

				$this->print_notice( 'Booking Product imported successfully!', 'success' );

				return;
			} catch ( Exception $e ) {
				$this->print_notice( $e->getMessage() );

				return;
			}
		}

		/**
		 * Removes duplicate/extra entries in _wc_booking_relationships table created by bug in 1.10 beta
		 *
		 * @since 1.0.1
		 * @version 1.0.1
		 */
		public function clean_up() {

			global $wpdb;

			// query all products that have resources, both with 'yes' or the 2.7 bool '1'
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => '-1',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'   => '_wc_booking_has_resources',
						'value' => '1',
						),
					array(
						'key'   => '_wc_booking_has_resources',
						'value' => 'yes',
						),
					),
				);
			$products = get_posts( $args );

			if ( empty( $products ) || 0 >= count( $products ) ) {
				throw new Exception( 'No products with resources found.' );
			}

			/**
			 * Go through each product and get its resources
			 * Create a new array of resources and enter those into the database
			 * Then use the old array to remove all previous resources
			 */
			foreach ( $products as $product ) {

				$resources = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d ORDER BY sort_order ASC", $product->ID ), ARRAY_A );

				if ( empty( $resources ) || 0 >= count( $resources ) ) {
					continue;
				}

				$cleaned_resources = array();
				foreach ( $resources as $resource ) {

					$cleaned_resources[ $resource['resource_id'] ] = $resource;
				}

				foreach ( $cleaned_resources as $resource ) {

					$wpdb->insert(
						"{$wpdb->prefix}wc_booking_relationships",
						array(
							'product_id'  => $resource['product_id'],
							'resource_id' => $resource['resource_id'],
							'sort_order'  => $resource['sort_order'],
						)
					);
				}

				foreach ( $resources as $resource ) {

					$wpdb->delete(
						"{$wpdb->prefix}wc_booking_relationships",
						array(
							'ID' => $resource['ID'],
						)
					);
				}
			}

			$this->print_notice( 'Booking resource relationships cleaned up successfully!', 'success' );
		}

		/**
		 * Checks if string is valid JSON.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 * @param string $string
		 * @return bool
		 */
		public function is_json( $string = '' ) {
			json_decode( $string );
			
			return ( JSON_ERROR_NONE === json_last_error() );
		}

		/**
		 * Prints notices.
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 * @param string $message
		 * @param string $type
		 */
		public function print_notice( $message = '', $type = 'warning' ) {
			$this->notice = '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	new Bookings_Helper();
}
