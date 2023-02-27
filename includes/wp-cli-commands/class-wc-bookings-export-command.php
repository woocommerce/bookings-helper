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
			try {
				$booking_export = new WC_Bookings_Helper_Export();
				WP_CLI::print_value( $booking_export->get_all_booking_products_data() );
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			return;
		}

		WP_CLI::error( 'Please provide a --all to export all booking products.' );
	}
}
