<?php

/**
 * Class for export functionality.
 */
class WC_Booking_Helper_Export extends WC_Bookings_Helper_Utils {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'init', array( $this, 'catch_export_requests' ), 20 );
	}

	/**
	 * Catches form requests.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function catch_export_requests() {
		if ( ! isset( $_GET['page'] ) || 'bookings-helper' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		if (
			'export_globals' !== $_POST['action'] &&
			'export_product' !== $_POST['action']
		) {
			return;
		}

		if (
			! wp_verify_nonce( $_POST['_wpnonce'], 'export_globals' ) &&
			! wp_verify_nonce( $_POST['_wpnonce'], 'import_globals' ) &&
			! wp_verify_nonce( $_POST['_wpnonce'], 'export_product' ) &&
			! wp_verify_nonce( $_POST['_wpnonce'], 'import_product' )
		) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		switch ( $_POST['action'] ) {
			case 'export_globals':
				$this->export_global_rules();
				break;

			case 'export_product':
				$this->export_product();
				break;
		}
	}

	/**
	 * Exports global availability rules file for browser download.
	 *
	 * @param 1.0.0
	 *
	 * @since 1.0.0
	 */
	public function export_global_rules() {
		try {
			if ( version_compare( WC_BOOKINGS_VERSION, '1.13.0', '<' ) ) {
				$global_rules = get_option( 'wc_global_booking_availability', array() );
			} else {
				$global_rules = WC_Data_Store::load( 'booking-global-availability' )->get_all_as_array();
			}

			if ( empty( $global_rules ) ) {
				throw new Exception( __( 'There are no rules to export.', 'bookings-helper' ) );
			}

			$global_rules_json = json_encode( $global_rules );

			$this->trigger_download( $global_rules_json, 'bookings-global-rules' );
		} catch ( Exception $e ) {
			$this->wc_bookings_helper_prepare_notice( $e->getMessage() );

			return;
		}
	}

	/**
	 * Exports a specific product by ID.
	 *
	 * @since 1.0.0
	 * @version 1.0.1
	 */
	public function export_product() {
		try {
			$product_id     = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : '';
			$product_status = get_post_status( $product_id );

			if ( empty( $product_id ) || empty( $product_status ) ) {
				throw new Exception( __( 'This booking product does not exist!', 'bookings-helper' ) );
			}

			global $wpdb;

			// Products.
			$product = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = 'product' AND ID = %d", $product_id ), ARRAY_A );

			// Get the type of the product, accomm or booking.
			$product_type       = wp_get_post_terms( $product[0]['ID'], 'product_type' );
			$product[0]['type'] = $product_type[0]->name;

			if ( empty( $product ) ) {
				throw new Exception( __( 'This booking product does not exist!', 'bookings-helper' ) );
			}

			// Product metas.
			$product_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND ( meta_key LIKE '%%wc_booking%%' OR meta_key = '_resource_base_costs' OR meta_key = '_resource_block_costs' OR meta_key = '_wc_display_cost' OR meta_key = '_virtual' )", $product_id ), ARRAY_A );

			if ( empty( $product_meta ) ) {
				throw new Exception( __( 'This booking product does not exist!', 'bookings-helper' ) );
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
			$this->wc_bookings_helper_prepare_notice( $e->getMessage() );

			return;
		}
	}
}

new WC_Booking_Helper_Export();
