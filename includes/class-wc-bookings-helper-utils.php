<?php

/**
 * Class for util functions.
 */
class WC_Bookings_Helper_Utils {

	/**
	 * Notices.
	 *
	 * @since 1.0.4
	 * @var string
	 */
	private $notice = '';

	/**
	 * Temporary directory path.
	 *
	 * @since 1.0.2
	 * @version 1.0.2
	 * @var string
	 */
	public $temp_dir;

	/**
	 * Checks to see if ZipArchive library exists.
	 *
	 * @since 1.0.2
	 * @version 1.0.2
	 * @var boolean
	 */
	public $ziparchive_available;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->temp_dir             = get_temp_dir() . 'bookings-helper';
		$this->ziparchive_available = class_exists( 'ZipArchive' );

		add_action( 'admin_notices', array( $this, 'wc_bookings_helper_show_notice' ) );
	}

	/**
	 * Prints notices.
	 *
	 * @param string $message Message to show.
	 * @param string $type Type of notice.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function wc_bookings_helper_prepare_notice( $message = '', $type = 'warning' ) {
		$this->notice = '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Prints notices.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function wc_bookings_helper_show_notice() {
		if ( ! empty( $this->notice ) ) {
			echo $this->notice;
		}
	}

	/**
	 * Cleans up lingering files and folder during transfer.
	 *
	 * @since 1.0.2
	 * @version 1.0.2
	 *
	 * @param string|null $path Folder path.
	 */
	public function clean_up( $path = null ) {
		if ( null === $path ) {
			$path = $this->temp_dir;
		}

		if ( is_dir( $path ) ) {
			$objects = scandir( $path );

			foreach ( $objects as $object ) {
				if ( '.' !== $object && '..' !== $object ) {
					if ( is_dir( $path . '/' . $object ) ) {
						$this->clean_up( $path . '/' . $object );
					} else {
						unlink( $path . '/' . $object );
					}
				}
			}

			rmdir( $path );
		}
	}

	/**
	 * Creates the zip file.
	 *
	 * @param string      $filename Name of file.
	 * @param JSON string $data | Data to be zipped.
	 *
	 * @return bool
	 * @version 1.0.2
	 *
	 * @since 1.0.2
	 */
	public function create_zip( $filename, $data = false ) {
		$zip_file = $this->temp_dir . '/' . $filename . '.zip';

		$zip = new ZipArchive();
		$zip->open( $zip_file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE );
		$zip->addFromString( $filename . '.json', $data );
		$zip->close();

		if ( file_exists( $this->temp_dir . '/' . $filename . '.zip' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Triggers the download feature of the browser.
	 *
	 * @param string $data Data to add to file.
	 * @param string $prefix File prefix to use.
	 *
	 * @throws Exception Show error if something goes wrong.
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function trigger_download( $data = '', $prefix = '' ) {
		if ( empty( $data ) ) {
			return;
		}

		@set_time_limit( 0 );

		// Disable GZIP.
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}

		@ini_set( 'zlib.output_compression', 'Off' );
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'output_handler', '' );

		$filename_prefix = $prefix;

		if ( $this->ziparchive_available ) {
			$filename = sprintf( '%1$s-%2$s', $filename_prefix, date( 'Y-m-d', current_time( 'timestamp' ) ) );

			$this->prep_transfer();

			$this->render_headers( $filename );

			if ( $this->create_zip( $filename, $data ) ) {
				readfile( $this->temp_dir . '/' . $filename . '.zip' );

				$this->clean_up();

				exit;
			} else {
				throw new Exception( __( 'Unable to export!', 'bookings-helper' ) );
			}
		} else {
			$filename = sprintf( '%1$s-%2$s.json', $filename_prefix, date( 'Y-m-d', current_time( 'timestamp' ) ) );

			$this->render_headers( $filename );

			file_put_contents( 'php://output', $data );

			exit;
		}
	}

	/**
	 * Opens the zip file.
	 *
	 * @throws Exception Show error if something goes wrong.
	 * @version 1.0.2
	 * @since 1.0.2
	 */
	public function open_zip() {
		$zip = new ZipArchive();

		if ( true === $zip->open( $_FILES['import']['tmp_name'] ) ) {
			$zip->extractTo( $this->temp_dir );
			$zip->close();

			$dir       = scandir( $this->temp_dir );
			$json_file = '';

			/**
			 * The zip may or may not contain other hidden
			 * system files so we must only extract the .json file.
			 */
			foreach ( $dir as $file ) {
				if ( preg_match( '/.json/', $file ) ) {
					$json_file = $file;
					break;
				}
			}

			if ( ! file_exists( $this->temp_dir . '/' . $json_file ) ) {
				throw new Exception( __( 'Unable to open zip file', 'bookings-helper' ) );
			}

			return file_get_contents( $this->temp_dir . '/' . $json_file );
		} else {
			throw new Exception( __( 'Unable to open zip file', 'bookings-helper' ) );
		}
	}

	/**
	 * Prepares the directory for file transfer.
	 *
	 * @since 1.0.2
	 * @version 1.0.2
	 *
	 * @return bool|void
	 */
	public function prep_transfer() {
		if ( ! is_dir( $this->temp_dir ) ) {
			return mkdir( $this->temp_dir );
		}
	}

	/**
	 * Renders the HTTP headers
	 *
	 * @param string $filename Path to file.
	 *
	 * @version 1.0.2
	 * @since 1.0.2
	 */
	public function render_headers( $filename ) {
		$type = 'json';

		if ( $this->ziparchive_available ) {
			$type = 'zip';
		}

		header( 'Content-Type: application/' . $type . '; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename . '.' . $type );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	/**
	 * Checks if string is valid JSON.
	 *
	 * @param string $string String to check.
	 *
	 * @return bool
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function is_json( $string = '' ) {
		json_decode( $string );

		return ( JSON_ERROR_NONE === json_last_error() );
	}

}

new WC_Bookings_Helper_Utils();
