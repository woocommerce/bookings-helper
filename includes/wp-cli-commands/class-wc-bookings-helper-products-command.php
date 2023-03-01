<?php
/**
 * The WP CLI command for handle commands for booking products.
 *
 * @package Bookings Helper/ WP CLI commands
 * @since   x.x.x
 */

use WP_CLI\ExitException;

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Class WC_Bookings_Export_Command
 *
 * @since x1.0.6
 */
class WC_Bookings_Helper_Products_Command extends WP_CLI_Command {
	/**
	 * Exports booking products.
	 *
	 * ## OPTIONS
	 * [--all]
	 * : Whether or not export all booking products
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
	 *
	 * @since x1.0.6
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

		try {
			$name_prefix = sprintf(
				'booking-products-%s-%s',
				date( 'Y-m-d', current_time( 'timestamp' ) ), // phpcs:ignore
				substr( uniqid( '', false ), 0, 5 )
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
					$booking_products_data[ $product_id ] = ( new WC_Bookings_Helper_Export() )->get_booking_product_data( $product_id );
				}

				$export_data = wp_json_encode( $booking_products_data );
			} else {
				$export_data = ( new WC_Bookings_Helper_Export() )->get_all_booking_products_data();
			}

			$zip->addFromString( $json_file_name, $export_data );

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
	 * [--file=<absolute_path_to_zip_file>]
	 * : The zip file path to import the booking global availability rules
	 *
	 *
	 * ## EXAMPLES
	 * wp booking-helper-products import --all --file=/path/to/absolute_path_to_zip_file
	 *
	 * @since x1.0.6
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

		$file_path      = $assoc_args['file'];
		$file_name      = basename( $assoc_args['file'], '.zip' );
		$json_file_path = dirname( $file_path ) . '/' . $file_name . '.json';
		$zip            = new ZipArchive();

		if ( $zip->open( $assoc_args['file'] ) !== true ) {
			WP_CLI::error( 'Booking products import failed. Please provide valid file path.' );

			return;
		}

		$zip->extractTo( dirname( $file_path ) );
		$zip->close();

		$products = file_get_contents( $json_file_path ); // phpcs:ignore
		unlink( $json_file_path );

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

		WP_CLI::success( 'Booking products imported successfully.' );
	}
}
