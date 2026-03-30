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
 * Validate and sanitize user role
 *
 * @param string $role to sanitize and validate.
 * @throws Exception If validation fails.
 */
function sanitize_role( $role ) {
	$role = sanitize_str( $role );

	$is_valid = in_array( $role, array( 'admin', 'user' ), true );

	if ( $is_valid ) {
		return $role;
	} else {
		throw new Exception( 'Invalid role' );
	}
}

/**
 * Check if the password is valid.
 *
 * @param string $password to validate.
 * @throws Exception If validation fails.
 */
function is_valid_password( $password ) {

	$uppercase     = preg_match( '@[A-Z]@', $password );
	$lowercase     = preg_match( '@[a-z]@', $password );
	$number        = preg_match( '@[0-9]@', $password );
	$special_chars = preg_match( '@[^\w]@', $password );

	$is_valid = $uppercase && $lowercase && $number && $special_chars && strlen( $password ) >= 8;

	if ( $is_valid ) {
		return true;
	} else {
		throw new Exception( 'Password should be at least 8 characters in length, should include at least one upper case letter, one number and one special character.' );
	}
}

/**
 * Generate random string.
 * Uses random_int() for cryptographically secure, uniformly selected integer.
 *
 * @param int $length the length of the random string.
 */
function generate_random_string( $length = 32 ) {
	// $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	// $selected   = array();
	// $max        = mb_strlen( $characters, '8bit' ) - 1;

	// for ( $i = 0; $i < $length; ++$i ) {
	// $pieces[] = $characters[ random_int( 0, $max ) ];
	// }
	// return implode( '', $pieces );

	// Use cryptographically secure random bytes and make the string url safe.
	return str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), base64_encode( random_bytes( $length ) ) ); // phpcs:disable

	// return urlencode( base64_encode( random_bytes( $length ) ) ); // phpcs:disable
}
