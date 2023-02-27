<?php
/**
 * The WP CLI command for handle commands for booking availability rules.
 *
 * @package Bookings Helper/ WP CLI commands
 * @since   x.x.x
 */

use WP_CLI\ExitException;

if( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Class WC_Bookings_Helper_Availability_Rules_Command
 * @since x.x.x
 */
class WC_Bookings_Helper_Availability_Rules_Command extends WP_CLI_Command {
	/**
	 * Export global availability rules.
	 *
	 * ## OPTIONS
	 *
	 * [--dir=<absolute_path_to_dir>]
	 * : The directory path to export the booking products
	 * ---
	 * default: wp-content/uploads
	 * value: path/to/export
	 * ---
	 *
	 * ## EXAMPLES
	 * wp booking-helper-availability-rules export
	 * wp booking-helper-availability-rules export --dir=/path/to/export
	 *
	 * @since x.x.x
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException
	 */
	public function export( array $args, array $assoc_args ) {
		// Default path is wp-content/uploads.
		$directory_path = empty( $assoc_args['dir'] ) ?
			trailingslashit( WP_CONTENT_DIR ) . 'uploads' :
			$assoc_args['dir'];

		try {
			$name_prefix = 'bookings-global-rules';

			$zip_file_path  = "$directory_path/$name_prefix.zip";
			$json_file_name = "$name_prefix.json";

			// Create zip;
			$zip = new ZipArchive();
			$zip->open( $zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

			$zip->addFromString(
				$json_file_name,
				// Get json data for all booking products.
				( new WC_Bookings_Helper_Export() )->get_global_availability_rules()
			);

			$zip->close();

			if ( $zip->open( $zip_file_path ) !== true ) {
				WP_CLI::error( 'Booking global availability rules export failed.' );

				return;
			}

			WP_CLI::success( "Booking global availability rules exported. Location: $zip_file_path" );
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}
}
