<?php
/**
 * Handle authentication, reset password etc.
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;

/**
 * Class Auth
 */
final class Auth {

	private $db;
	private $logger;
	private $mailer;
	private $cipher = 'aes-256-cbc';

	public function __construct() {
		$this->db = new SQLite3( DATABASE );
		$this->db->enableExceptions( true );
		$this->logger = new Logger();
		$this->mailer = new Mailer();
	}

	/**
	 * Log in
	 *
	 * @param object $user the user to log in for.
	 * @param object $post_data the post data.
	 *
	 * @throws \Exception If outhorization fails.
	 */
	public function log_user_in( $user, $post_data ) {

		$username = $post_data->username;
		$password = $post_data->password;

		$statement = $this->db->prepare( 'SELECT * FROM "users" WHERE "username" = ?' );
		$statement->bindValue( 1, $username );
		$result    = $statement->execute();
		$user_data = $result->fetchArray( SQLITE3_ASSOC );
		$result->finalize();

		if ( ! $user_data ) {
			throw new \Exception( 'User not found.' );
		}

		// Verify password and set login cookie.
		if ( password_verify( $password, $user_data['password'] ) ) {

			$user->id = $user_data['id'];
			$time     = time(); // define time variable here to ease unit testing.

			$options = array(
				'expires'  => $time + 3600,
				'path'     => '/',
				'secure'   => true,
				'httponly' => false,
				'samesite' => 'Strict',
			);
			setcookie(
				'HSA_TOKEN',
				$user->id . '_' . $this->create_token( $user, $time ),
				$options
			);
			send_response_and_exit( 200, 'success', 'Logged in.' );

		} else {
			throw new \Exception( 'Unauthorized.' );
		}

		exit; // All done.
	}


	/**
	 * Logout
	 */
	public function log_user_out() {
		setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.
		send_response_and_exit( 200, 'success', 'Logged out.' );
	}


	/**
	 * Check if user is or should be logged in by validating the token data in cookie.
	 *
	 * @param object $user the user to check the log in status for.
	 */
	public function is_user_logged_in( $user ) {

		$cookie_value = isset( $_COOKIE['HSA_TOKEN'] ) ? $_COOKIE['HSA_TOKEN'] : '';

		if ( ! $cookie_value ) {
			return false;
		}

		$splitted_cookie = explode( '_', $cookie_value, 2 );
		$user_id         = isset( $splitted_cookie[0] ) && $splitted_cookie[0] ? $splitted_cookie[0] : null;
		$token           = isset( $splitted_cookie[1] ) && $splitted_cookie[1] ? $splitted_cookie[1] : null;

		if ( ! $user_id || ! $token ) {
			return false;
		}

		$token_data  = openssl_decrypt(
			$token,
			$this->cipher,
			$_ENV['APP_SECRET_KEY'], // A secret key from enviroment vars.
			$options = 0,
			$user->get_init_vector( $user_id )  // User specific IV from the database.
		);
		$token_data = json_decode( $token_data, true );

		$is_valid_user = isset( $token_data['user_id'] )
						&& intval( $token_data['user_id'] ) === intval( $user_id );
		$cookietime    = isset( $token_data['timestamp'] ) ? intval( $token_data['timestamp'] ) : 0;
		$timenow       = intval( time() );
		$is_valid_time = ( $timenow - $cookietime ) < 3600; // Max login time is 1 hour.

		if ( $is_valid_user && $is_valid_time ) {
			$user->id = $user_id;
			return true;
		}

		return false;
	}


	/**
	 * Create a cryptographically secure token for login cookie.
	 *
	 * @param string $time The timestamp.
	 * @throws \Exception When somethin goes wrong.
	 */
	public function create_token( $user, $time ) {

		$data = json_encode(
			array(
				'user_id'   => $user->id,
				'timestamp' => $time,
			)
		);

		if ( in_array( $this->cipher, openssl_get_cipher_methods(), true ) ) {

			$ivlen       = openssl_cipher_iv_length( $this->cipher );
			$init_vector = openssl_random_pseudo_bytes( $ivlen );

			$token       = openssl_encrypt(
				$data,
				$this->cipher,
				$_ENV['APP_SECRET_KEY'], // The secret key from enviroment vars.
				$options = 0,
				$init_vector,
			);

			if ( ! $token ) {
				throw new \Exception( 'Error Processing Request' );
			}

			// Save IV to the the database.
			$user->save_init_vector( $init_vector );

			return $token;
		} else {
			throw new \Exception( 'Error Processing Request' );
		}
	}
}
