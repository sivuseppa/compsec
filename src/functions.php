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

/**
 * Sanitize and validate email
 *
 * @param string $email to sanitize and validate.
 * @throws Exception If validation fails.
 */
function sanitize_email( $email ) {
	$sanitized_email = filter_var( $email, FILTER_SANITIZE_EMAIL );

	if ( filter_var( $sanitized_email, FILTER_VALIDATE_EMAIL ) ) {
		return $sanitized_email;
	} else {
		throw new Exception( 'Invalid email' );
	}
}

/**
 * Sanitize string input
 *
 * @param string $input to sanitize and validate.
 */
function sanitize_str( $input ) {
	return htmlspecialchars( $input );
}

/**
 * Validate user role
 *
 * @param string $role to sanitize and validate.
 * @throws Exception If validation fails.
 */
function validate_role( $role ) {
	$role = sanitize_str( $role );

	$is_valid = in_array( $role, array( 'admin', 'user' ), true );

	if ( $is_valid ) {
		return $role;
	} else {
		throw new Exception( 'Invalid role' );
	}
}
