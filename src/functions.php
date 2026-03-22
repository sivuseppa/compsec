<?php
/**
 * Helper functions
 *
 * @package CompSecApp
 */

/**
 * Send http response and exit
 *
 * @param int    $code response code.
 * @param string $status response status.
 * @param string $message response message.
 */
function send_response_and_exit( $code, $status, $message ) {
	http_response_code( $code );
	echo json_encode(
		array(
			'status'  => $status,
			'message' => $message,
		)
	);
	exit;
}
