<?php

/**
 * Class for import functionality.
 */
class WC_Bookings_Helper_Import extends WC_Bookings_Helper_Utils {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'init', array( $this, 'catch_import_requests' ), 20 );
	}

	/**
	 * Catches form requests.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function catch_import_requests() {
		if ( ! isset( $_GET['page'] ) || 'bookings-helper' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		if (
			'import_globals' !== $_POST['action'] &&
			'import_product' !== $_POST['action']
		) {
			return;
		}

		if (
			! wp_verify_nonce( $_POST['_wpnonce'], 'import_globals' ) &&
			! wp_verify_nonce( $_POST['_wpnonce'], 'import_product' )
		) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		switch ( $_POST['action'] ) {
			case 'import_globals':
				$this->import_global_rules();
				break;

			case 'import_product':
				$this->import_product();
				break;
		}
	}

	/**
	 * Imports global availability rules from file.
	 *
	 * @since 1.0.3 Add compatibility with Bookings custom global availability tables.
	 * @throws Exception Show error if file isn't valid.
	 */
	public function import_global_rules() {
		try {
			if ( empty( $_FILES ) || empty( $_FILES['import'] ) || 0 !== $_FILES['import']['error'] || empty( $_FILES['import']['tmp_name'] ) ) {
				throw new Exception( __( 'There are no rules to import or file is not valid.', 'bookings-helper' ) );
			} else {
				if ( $_FILES['import']['size'] > 1000000 ) {
					throw new Exception( __( 'The file exceeds 1MB.', 'bookings-helper' ) );
				}

				if ( $this->ziparchive_available ) {
					$global_rules_json = $this->open_zip();
				} else {
					$global_rules_json = file_get_contents( $_FILES['import']['tmp_name'] );
				}

				if ( ! $this->is_json( $global_rules_json ) ) {
					throw new Exception( __( 'The file is not in a valid JSON format.', 'bookings-helper' ) );
				}
			}

			$global_rules = json_decode( $global_rules_json, true );

			// Sanitize.
			array_walk_recursive( $global_rules, 'wc_clean' );

			if ( version_compare( WC_BOOKINGS_VERSION, '1.13.0', '<' ) ) {
				/*
				 * For some strange reason update_option is not working here so
				 * had to revert to delete the option and add it again.
				 */
				delete_option( 'wc_global_booking_availability' );
				add_option( 'wc_global_booking_availability', $global_rules );
			} else {
				global $wpdb;

				// First delete all data from table.
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_bookings_availability" );

				foreach ( $global_rules as $rule ) {
					$wpdb->insert(
						$wpdb->prefix . 'wc_bookings_availability',
						array(
							'gcal_event_id' => ! empty( $rule['gcal_event_id'] ) ? $rule['gcal_event_id'] : '',
							'title'         => $rule['title'],
							'range_type'    => $rule['range_type'],
							'from_date'     => $rule['from_date'],
							'to_date'       => $rule['to_date'],
							'from_range'    => $rule['from_range'],
							'to_range'      => $rule['to_range'],
							'bookable'      => $rule['bookable'],
							'priority'      => $rule['priority'],
							'ordering'      => $rule['ordering'],
							'date_created'  => $rule['date_created'],
							'date_modified' => $rule['date_modified'],
							'rrule'         => ! empty( $rule['rrule'] ) ? $rule['rrule'] : '',
						),
						array(
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%d',
							'%d',
							'%s',
							'%s',
						)
					);
				}
			}

			$this->wc_bookings_helper_prepare_notice( __( 'Global Availability Rules imported successfully!', 'bookings-helper' ), 'success' );
			$this->clean_up();

			return;
		} catch ( Exception $e ) {
			$this->wc_bookings_helper_prepare_notice( $e->getMessage() );

			return;
		}
	}


	/**
	 * Imports booking product from file.
	 *
	 * @since 1.0.0
	 * @version 1.0.1
	 * @throws Exception Show error if something goes wrong.
	 */
	public function import_product() {
		try {
			if ( empty( $_FILES ) || empty( $_FILES['import'] ) || 0 !== $_FILES['import']['error'] || empty( $_FILES['import']['tmp_name'] ) ) {
				throw new Exception( __( 'There is no bookable product to import or file is not valid.', 'bookings-helper' ) );
			} else {
				if ( $_FILES['import']['size'] > 1000000 ) {
					throw new Exception( __( 'The file exceeds 1MB.', 'bookings-helper' ) );
				}

				if ( $this->ziparchive_available ) {
					$product_json = $this->open_zip();
				} else {
					$product_json = file_get_contents( $_FILES['import']['tmp_name'] );
				}

				if ( ! $this->is_json( $product_json ) ) {
					throw new Exception( __( 'The file is not in a valid JSON format.', 'bookings-helper' ) );
				}
			}

			$product = json_decode( $product_json, true );

			// Sanitize.
			array_walk_recursive( $product, 'wc_clean' );

			global $wpdb;

			// Product.
			$product_data = array(
				'post_title'   => sanitize_text_field( $product['product']['post_title'] ) . ' (bookings test #' . absint( $product['product']['ID'] ) . ')',
				'post_content' => sanitize_text_field( $product['product']['post_content'] ),
				'post_type'    => 'product',
				'post_status'  => 'publish',
			);

			$product_id = wp_insert_post( $product_data, false );

			if ( empty( $product_id ) ) {
				throw new Exception( 'Failed to create product.' );
			}

			// Product meta.
			foreach ( $product['product_meta'] as $meta ) {
//				add_post_meta( $product_id, sanitize_text_field( $meta['meta_key'] ), sanitize_text_field( $meta['meta_value'] ) );
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $product_id, sanitize_text_field( $meta['meta_key'] ), sanitize_text_field( $meta['meta_value'] ) ) );
			}

			$product_type = ! empty( $product['product']['type'] ) ? $product['product']['type'] : 'booking';
			wp_set_object_terms( $product_id, $product_type, 'product_type' );

			// Resources.
			if ( ! empty( $product['resources'] ) ) {
				$resource_base_costs      = unserialize( get_post_meta( $product_id, '_resource_base_costs', true ) );
				$new_resource_base_costs  = array();
				$resource_block_costs     = unserialize( get_post_meta( $product_id, '_resource_block_costs', true ) );
				$new_resource_block_costs = array();

				foreach ( $product['resources'] as $resource ) {
					$resource_data = array(
						'post_title'  => sanitize_text_field( $resource['resource']['post_title'] ) . ' (resource test #' . absint( $resource['resource']['ID'] ) . ')',
						'post_type'   => 'bookable_resource',
						'post_status' => 'publish',
					);

					$resource_id = wp_insert_post( $resource_data, false );

					if ( empty( $resource_id ) ) {
						throw new Exception( __( 'Failed to create resource.', 'bookings-helper' ) );
					}

					foreach ( $resource['resource_meta'] as $meta ) {
						add_post_meta( $resource_id, sanitize_text_field( $meta['meta_key'] ), sanitize_text_field( $meta['meta_value'] ) );
					}

					$new_resource_base_costs[ $resource_id ]  = ! empty( $resource_base_costs[ $resource['resource']['ID'] ] ) ? $resource_base_costs[ $resource['resource']['ID'] ] : '';
					$new_resource_block_costs[ $resource_id ] = ! empty( $resource_block_costs[ $resource['resource']['ID'] ] ) ? $resource_block_costs[ $resource['resource']['ID'] ] : '';
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wc_booking_relationships ( product_id, resource_id ) VALUES ( %d, %d )", $product_id, $resource_id ) );
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
						'post_title'   => sanitize_text_field( $person['person']['post_title'] ) . ' (person test #' . absint( $person['person']['ID'] ) . ')',
						'post_type'    => 'bookable_person',
						'post_status'  => 'publish',
						'post_parent'  => absint( $product_id ),
						'post_excerpt' => sanitize_text_field( $person['person']['post_excerpt'] ),
					);

					$person_id = wp_insert_post( $person_data, false );

					if ( empty( $person_id ) ) {
						throw new Exception( __( 'Failed to create person.', 'bookings-helper' ) );
					}

					foreach ( $person['person_meta'] as $meta ) {
						add_post_meta( absint( $person_id ), sanitize_text_field( $meta['meta_key'] ), sanitize_text_field( $meta['meta_value'] ) );
					}
				}
			}

			$this->wc_bookings_helper_prepare_notice( __( 'Booking Product imported successfully!', 'bookings-helper' ), 'success' );
			$this->clean_up();

			return;
		} catch ( Exception $e ) {
			$this->wc_bookings_helper_prepare_notice( $e->getMessage() );

			return;
		}
	}

}

new WC_Bookings_Helper_Import();
