<?php
/**
 * The WP CLI command for handle commands for booking products.
 *
 * @package Bookings Helper/ WP CLI commands
 * @since   1.0.6
 */

use WP_CLI\ExitException;

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Class WC_Bookings_Export_Command
 *
 * @since 1.0.6
 */
class WC_Bookings_Helper_Products_Command extends WP_CLI_Command {
	/**
	 * Exports booking products.
	 *
	 * ## OPTIONS
	 * [--all]
	 * : Whether export all booking products or not
	 *
	 * [--with-global-rules]
	 * : Whether export global availability rules or not
	 *
	 * [--dir=<absolute_path_to_dir>]
	 * : The directory path to export the booking products
	 *
	 * [--products=<product_ids>]
	 * : The booking product ids to export. ths product ids should be comma separated without spaces.
	 *
	 * ## EXAMPLES
	 * wp booking-helper-products export --all
	 * wp booking-helper-products export --all --dir=/path/to/export
	 * wp booking-helper-products export --products="68,73"
	 * wp booking-helper-products export --all --with-global-rules
	 *
	 * @since 1.0.6
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException WP CLI error.
	 */
	public function export( array $args, array $assoc_args ) {
		// Export all booking products.
		if ( empty( $assoc_args['all'] ) && empty( $assoc_args['products'] ) ) {
			WP_CLI::error( 'Please provide a --all to export all booking products.' );

			return;
		}

		// Default path is wp-content/uploads.
		$directory_path = empty( $assoc_args['dir'] ) ?
			trailingslashit( WP_CONTENT_DIR ) . 'uploads' :
			$assoc_args['dir'];

		$is_export_with_global_rules = ! empty( $assoc_args['with-global-rules'] );

		try {
			$name_prefix = sprintf(
				'booking-products%s-%s-%s',
				$is_export_with_global_rules ? '-with-global-rules' : '',
				date( 'Y-m-d', current_time( 'timestamp' ) ), // phpcs:ignore
				substr( wp_generate_password(), 0, 5 )
			);

			$zip_file_path  = "$directory_path/$name_prefix.zip";
			$json_file_name = "$name_prefix.json";

			// Create zip.
			$zip = new ZipArchive();
			$zip->open( $zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

			// Get booking products data in json format.
			if ( ! empty( $assoc_args['products'] ) ) {
				$product_ids = array_map( 'absint', explode( ',', $assoc_args['products'] ) );

				foreach ( $product_ids as $product_id ) {
					$export_data['booking-products'][ $product_id ] = ( new WC_Bookings_Helper_Export() )->get_booking_product_data( $product_id );
				}
			} else {
				$export_data['booking-products'] = ( new WC_Bookings_Helper_Export() )->get_all_booking_products_data();
			}

			if ( $is_export_with_global_rules ) {
				$export_data['global-availability-rules'] = ( new WC_Bookings_Helper_Export() )->get_global_availability_rules();
			}

			$zip->addFromString( $json_file_name, wp_json_encode( $export_data ) );

			$zip->close();

			if ( $zip->open( $zip_file_path ) !== true ) {
				WP_CLI::error( 'Booking products export failed.' );

				return;
			}

			WP_CLI::success( "Booking products exported. Location: $zip_file_path" );
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Import booking products.
	 *
	 * ## OPTIONS
	 *
	 * [--with-global-rules]
	 * : Whether export global availability rules or not
	 *
	 * [--file=<absolute_path_to_zip_file>]
	 * : The zip file path to import the booking global availability rules
	 *
	 *
	 * ## EXAMPLES
	 * wp booking-helper-products import --all --file=/path/to/absolute_path_to_zip_file
	 *
	 * @since 1.0.6
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException WP CLI error.
	 */
	public function import( array $args, array $assoc_args ) {
		if ( empty( $assoc_args['file'] ) ) {
			WP_CLI::error( 'Please provide the zip file path to import the booking products.' );

			return;
		}

		$is_export_with_global_rules     = ! empty( $assoc_args['with-global-rules'] );
		$file_path                       = $assoc_args['file'];
		$file_name                       = basename( $assoc_args['file'], '.zip' );
		$file_directory_path             = dirname( $file_path );
		$booking_products_json_file_path = $file_directory_path . '/' . $file_name . '.json';
		$global_rules_json_file_path     = null;

		$zip = new ZipArchive();

		if ( $zip->open( $assoc_args['file'] ) !== true ) {
			WP_CLI::error( 'Booking products import failed. Please provide valid file path.' );

			return;
		}

		// Reset file path If import command has --with-global-rules.
		if ( $is_export_with_global_rules ) {
			$file_directory_path             = dirname( $file_path ) . '/' . $file_name;
			$booking_products_json_file_path = $file_directory_path . '/booking-products.json';
			$global_rules_json_file_path     = $file_directory_path . '/global-availability-rules.json';
		}

		$zip->extractTo( $file_directory_path );

		// Check if the zip file has global availability rules.
		// If import command has --with-global-rules then throw error.
		if (
			$is_export_with_global_rules &&
			! is_dir( dirname( $file_path ) . '/' . $file_name ) &&
			! file_get_contents( dirname( $file_path ) . '/' . $file_name . '/global-availability-rules.json' ) // phpcs:ignore
		) {
			// Remove extracted file.
			unlink( dirname( $file_path ) . "/$file_name.json" );

			WP_CLI::error( 'Booking products import failed. Remove --with-global-rules from command because this zip does not have global availability rules.' );

			return;
		}

		$zip->close();

		/**
		 * Import booking products.
		 */
		$products = file_get_contents( $booking_products_json_file_path ); // phpcs:ignore

		if ( empty( $products ) ) {
			WP_CLI::error( 'Booking products import failed. File does not have data to import.' );

			return;
		}

		$products = json_decode( $products, true );

		try {
			// Add support: user should be able to import data exported from WP Dashboard.
			// convert data to new format.
			if ( array_key_exists( 'product', $products ) ) {
				$temp_products                               = array();
				$temp_products[ $products['product']['ID'] ] = wp_json_encode( $products );
				$products                                    = $temp_products;
			}

			foreach ( $products as $product ) {
				( new WC_Bookings_Helper_Import() )->import_product_from_json( $product );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( 'Booking product import failed. Reason:' . $e->getMessage() );
		}

		/**
		 * Import global availability rules.
		 */
		if ( $is_export_with_global_rules ) {
			try {
				$global_availability_rules = file_get_contents( $global_rules_json_file_path ); // phpcs:ignore

				if ( empty( $global_availability_rules ) ) {
					WP_CLI::error( 'Booking products import failed. File does not have global availability rules to import.' );

					return;
				}

				( new WC_Bookings_Helper_Import() )->import_rules_from_json( $global_availability_rules );
			} catch ( Exception $e ) {
				WP_CLI::error( 'Booking product import failed. Reason:' . $e->getMessage() );
			}
		}

		/**
		 * Delete the extracted folder and file
		 */
		if ( $is_export_with_global_rules ) {
			unlink( $booking_products_json_file_path );
			unlink( $global_rules_json_file_path );

			// Remove extracted folder.
			// Directory will be removed only if it is empty.
			rmdir( $file_directory_path );
		} else {
			unlink( $booking_products_json_file_path );
		}

		WP_CLI::success( 'Booking products imported successfully.' );
	}
}
