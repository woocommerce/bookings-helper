<?php
/**
 * The WP CLI command for exporting bookings.
 *
 * @package Bookings Helper/ WP CLI commands
 * @since   x.x.x
 */

use WP_CLI\ExitException;

/**
 * Class WC_Bookings_Export_Command
 * @since x.x.x
 */
class WC_Bookings_Export_Command {
	/**
	 * Exports booking products.
	 *
	 * ## OPTIONS
	 * <--all>
	 * : Whether or not export all booking products
	 *
	 * <--dir=<absolute_path_to_dir>>
	 * : The directory path to export the booking products
	 * ---
	 * default: wp-content/uploads
	 * value: path/to/export
	 * ---
	 *
	 * ## EXAMPLES
	 * wp booking-helper export --all
	 * wp booking-helper export --all --dir=/path/to/export
	 *
	 * @since x.x.x
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException
	 */
	public function __invoke( $args, $assoc_args ) {
		// Export all booking products.
		if ( ! empty( $assoc_args['all'] ) ) {
			// Default path is wp-content/uploads.
			$directory_path = empty( $assoc_args['dir'] ) ?
				trailingslashit( WP_CONTENT_DIR ) . 'uploads' :
				$assoc_args['dir'];

			try {
				$name_prefix   = sprintf(
					'booking-product-%s',
					date( 'Y-m-d',
						current_time( 'timestamp' )
					)
				);

				$zip_file_path = "$directory_path/$name_prefix.zip";
				$json_file_name = "$name_prefix.json";

				// Create zip;
				$zip = new ZipArchive();
				$zip->open( $zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

				$zip->addFromString(
					$json_file_name,
					// Get json data for all booking products.
					( new WC_Bookings_Helper_Export() )->get_all_booking_products_data()
				);

				$zip->close();

				if( $zip->open( $zip_file_path ) !== true ) {
					WP_CLI::error( 'Booking products export failed.' );
					return;
				}

				WP_CLI::success( "Booking products exported. Location: $zip_file_path" );
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			return;
		}

		WP_CLI::error( 'Please provide a --all to export all booking products.' );
	}
}
