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
	 * [--with-global-availability-rules]
	 * : Whether export global availability rules or not
	 *
	 * [--dir=<absolute_path_to_dir>]
	 * : The directory path to export the booking products
	 *
	 * [--products=<product_ids>]
	 * : The booking product ids to export. ths product ids should be comma separated without spaces.
	 *
	 * ## EXAMPLES
	 * wp bookings-helper-products export --all
	 * wp bookings-helper-products export --all --dir=/path/to/export
	 * wp bookings-helper-products export --products="68,73"
	 * wp bookings-helper-products export --all --with-global-availability-rules
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
			untrailingslashit( $assoc_args['dir'] );

		$is_exporting_with_global_rules = ! empty( $assoc_args['with-global-availability-rules'] );

		// Create file name prefix on basis of export query.
		$name_prefix = sprintf(
			'booking-products%s-%s-%s',
			$is_exporting_with_global_rules ? '-with-global-availability-rules' : '',
			date( 'Y-m-d', current_time( 'timestamp' ) ), // phpcs:ignore
			time()
		);

		$zip_file_path  = "$directory_path/$name_prefix.zip";
		$json_file_name = "$name_prefix.json";

		try {
			$zip = new ZipArchive();
			$zip->open( $zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

			if ( ! empty( $assoc_args['products'] ) ) {
				// Export booking products.
				$product_ids = array_map( 'absint', explode( ',', $assoc_args['products'] ) );

				foreach ( $product_ids as $product_id ) {
					$export_data['booking-products'][ $product_id ] = ( new WC_Bookings_Helper_Export() )->get_booking_product_data( $product_id );
				}
			} else {
				// Export all booking products.
				$export_data['booking-products'] = ( new WC_Bookings_Helper_Export() )->get_all_booking_products_data();
			}

			if ( $is_exporting_with_global_rules ) {
				// Export global availability rules if requested.
				$export_data['global-availability-rules'] = ( new WC_Bookings_Helper_Export() )->get_global_availability_rules();
			}

			$zip->addFromString( $json_file_name, wp_json_encode( $export_data ) );

			$zip->close();

			if ( true !== $zip->open( $zip_file_path ) ) {
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
	 * [--with-global-availability-rules]
	 * : Whether export global availability rules or not
	 *
	 * [--file=<absolute_path_to_zip_file>]
	 * : The zip file path to import the booking global availability rules
	 *
	 *
	 * ## EXAMPLES
	 * wp bookings-helper-products import --all --file=/path/to/absolute_path_to_zip_file
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

		$is_export_with_global_rules = ! empty( $assoc_args['with-global-availability-rules'] );
		$file_path                   = $assoc_args['file'];
		$file_directory_path         = dirname( $file_path );

		$zip = new ZipArchive();

		if ( true !== $zip->open( $assoc_args['file'] ) ) {
			WP_CLI::error( 'Booking products import failed: Please provide valid file path.' );

			return;
		}

		$zip->extractTo( $file_directory_path );

		if( $zip->numFiles !== 1 ) {
			WP_CLI::error( 'Booking products import failed: Invalid zip file. More than one file exists in the zip file.' );
		}

		// Get file name from extracted zip file.
		$file_name      = $zip->getNameIndex( 0 );
		$json_file_path = $file_directory_path . '/' . $file_name;

		$zip->close();

		$json_data = json_decode( file_get_contents( $json_file_path ), true ); // phpcs:ignore

		/**
		 * Import booking products.
		 */

		if ( array_key_exists( 'product', $json_data ) ) {
			// Backward compatibility: user should be able to import data exported from WP Dashboard (User Interface).
			// convert data to new format.
			$temp_products = array();

			$temp_products[ $json_data['product']['ID'] ] = $json_data;

			$products = $temp_products;
		} else {
			$products = $json_data['booking-products'];
		}

		if ( empty( $products ) ) {
			WP_CLI::error( 'Booking products import failed: File does not have data to import.' );

			return;
		}

		try {
			foreach ( $products as $product ) {
				( new WC_Bookings_Helper_Import() )->import_product_from_json( wp_json_encode( $product ) );
			}

			WP_CLI::success( 'Booking products imported successfully.' );
		} catch ( Exception $e ) {
			WP_CLI::error( 'Booking product import failed: ' . $e->getMessage() );
		}

		/**
		 * Import global availability rules.
		 */
		if ( $is_export_with_global_rules ) {
			$global_availability_rules = null;

			if ( ! empty( $json_data['global-availability-rules'] ) ) {
				$global_availability_rules = wp_json_encode( $json_data['global-availability-rules'] );
			} elseif ( array_key_exists( 'product', $json_data ) && array_key_exists( 'global_rules', $json_data ) ) {
				// Backward compatibility: user should be able to import data exported from WP Dashboard (User Interface).
				// convert data to new format.
				$global_availability_rules = $json_data['global_rules'];
			} else {
				WP_CLI::warning( 'Booking products import: File does not have global availability rules to import.' );
			}

			if ( $global_availability_rules ) {
				try {
					( new WC_Bookings_Helper_Import() )->import_rules_from_json( $global_availability_rules );
				} catch ( Exception $e ) {
					WP_CLI::error( 'Booking product import failed: ' . $e->getMessage() );
				}

				WP_CLI::success( 'Booking product global availability rules imported successfully.' );
			}
		}

		// Delete the json file.
		unlink( $json_file_path );
	}
}
