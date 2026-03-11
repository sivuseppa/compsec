<?php
/**
 * Helper class to write a log.
 *
 * @package SweetHomeApp
 */

namespace SweetHomeApp;

/**
 * Class Logger
 */
final class Logger {

	private $logfile = BACKEND_ROOT . '/includes/logs/error.log';

	public function write( $data ) {

		$datetime = '[ ' . date( 'Y-m-d H:i:s' ) . ' ] ';

		if ( is_array( $data ) || is_object( $data ) ) {
			error_log( $datetime . print_r( $data, true ) . "\n", 3, $this->logfile ); // phpcs:ignore
		} else {
			error_log( $datetime . $data . "\n", 3, $this->logfile ); // phpcs:ignore
		}
	}
}
