<?php
/**
 * The model of the User
 *
 * @package SweetHomeApp
 */

namespace SweetHomeApp;

use SQLite3;

/**
 * The User model class
 */
final class User {

	private $db;
	public $id     = null;
	public $role   = 'viewer';
	public $cipher = 'aes-256-cbc';

	/**
	 * Constuctor
	 *
	 * @param int $user_id The id of the user.
	 */
	public function __construct( $user_id = null ) {
		$this->db = new SQLite3( DATA_DIR . 'db/db.sqlite' );
		$this->id = $user_id;
	}


	/**
	 * Chech if the current user is admin or not.
	 */
	public function is_admin() {
		return true; // TODO.
	}


	/**
	 * Create a cryptographically secure token for login.
	 *
	 * @param string $time The timestamp.
	 * @throws \Exception When somethin goes wrong.
	 */
	public function create_token( $time ) {

		$data = json_encode(
			array(
				'user_id'   => $this->id,
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
			$this->save_init_vector( $init_vector );

			return $token; // phpcs:ignore
		} else {
			throw new \Exception( 'Error Processing Request' );
		}
	}


	/**
	 * Log in
	 *
	 * @param object $post_data the post data.
	 * @throws \Exception If outhorization fails.
	 */
	public function login( $post_data ) {

		$username = $post_data->username;
		$password = $post_data->password;

		try {
			$statement = $this->db->prepare( 'SELECT * FROM "users" WHERE "username" = ?' );
			$statement->bindValue( 1, $username );
			$result    = $statement->execute();
			$user_data = $result->fetchArray( SQLITE3_ASSOC );

			if ( ! $user_data ) {
				throw new \Exception( 'User not found.' );
			}

			// Verify password and set login cookie.
			if ( password_verify( $password, $user_data['password'] ) ) {

				$this->id = $user_data['id'];
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
					$this->id . '_' . $this->create_token( $time ),
					$options
				);
				echo json_encode(
					array(
						'status' => 'success',
						'data'   => 'User logged in.',
					)
				);
			} else {
				throw new \Exception( 'Unouthorized.' );
			}
		} catch ( \Throwable $exception ) {
			setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.

			http_response_code( 401 );
			echo json_encode(
				array(
					'status'  => 'error',
					'message' => $exception->getMessage(),
				)
			);
		}
		exit; // All done.
	}


	/**
	 * Check if user is or should be logged in by validating the token data in cookie.
	 */
	public function is_logged_in() {

		$cookie_value    = isset( $_COOKIE['HSA_TOKEN'] ) ? $_COOKIE['HSA_TOKEN'] : '';
		$splitted_cookie = explode( '_', $cookie_value, 2 );
		$this->id        = $splitted_cookie[0];
		$token           = $splitted_cookie[1];

		$token_data  = openssl_decrypt(
			$token,
			$this->cipher,
			$_ENV['APP_SECRET_KEY'], // A secret key from enviroment vars.
			$options = 0,
			$this->get_init_vector()  // User specific IV from the database.
		);
		$token_data = json_decode( $token_data, true );

		$is_valid_user = isset( $token_data['user_id'] )
						&& intval( $token_data['user_id'] ) === intval( $this->id );
		$cookietime    = isset( $token_data['timestamp'] ) ? intval( $token_data['timestamp'] ) : '';
		$timenow       = intval( time() );
		$is_valid_time = ( $timenow - $cookietime ) < 3600; // Max login time is 1 hour.

		return $is_valid_user && $is_valid_time;
	}


	/**
	 * Save initialization vector the database among other user data
	 * to be used later on token validation process.
	 *
	 * @param string $iv The initialization vector.
	 */
	public function save_init_vector( $iv ) {
		$statement = $this->db->prepare(
			'UPDATE users SET iv = :iv
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':iv', $iv );
		$statement->execute();
	}


	/**
	 * Return initialization vector from the database.
	 */
	public function get_init_vector() {
		$statement = $this->db->prepare(
			'SELECT iv FROM users
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$results   = $statement->execute();
		$user_data = $results->fetchArray( SQLITE3_ASSOC );

		return isset( $user_data['iv'] ) ? $user_data['iv'] : '';
	}


	/**
	 * Logout
	 */
	public function logout() {
		setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.
		http_response_code( 200 );
		echo json_encode(
			array(
				'status'  => 'success',
				'message' => 'logged out',
			)
		);
		exit;
	}


	/**
	 * Add new user to the database.
	 *
	 * @param Object $data POST data.
	 */
	public function add( $data ) {
		try {

			$username = $data->username;
			$password = $data->password;
			$role     = $data->role;

			// Insert potentially unsafe data with a prepared statement and named parameters.
			$statement = $this->db->prepare(
				'INSERT INTO "users" ("id", "username", "password", "role")
    			VALUES (:id, :username, :password, :role)'
			);
			$statement->bindValue( ':id', null );
			$statement->bindValue( ':username', $username );
			$statement->bindValue( ':password', password_hash( $password, PASSWORD_BCRYPT ) );
			$statement->bindValue( ':role', $role );
			$statement->execute();

			header( 'Content-Type: application/json' );
			echo json_encode(
				array(
					'status' => 'success',
					'data'   => 'User added?',
				)
			);

		} catch ( \Throwable $exception ) {
			http_response_code( 500 );
			echo json_encode(
				array(
					'status'  => 'error',
					'message' => $exception,
				)
			);
			exit;
		}
	}
}
