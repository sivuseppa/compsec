<?php
/**
 * The model of the User
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;

/**
 * The User model class
 */
final class User {

	private $db;
	public $id = null;
	public $username;
	public $password;
	public $email;
	public $role   = '';
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
	 * Save userdata to the database.
	 */
	public function save() {

		// Insert potentially unsafe data with a prepared statement and named parameters.
		if ( $this->id ) {
			$statement = $this->db->prepare(
				'UPDATE users 
				SET username = :username, email = :email, password = :password, role = :role
				WHERE id = :id'
			);
		} else {
			$statement = $this->db->prepare(
				'INSERT INTO users ("id", email, "username", "password", "role")
				VALUES (:id, :email, :username, :password, :role)'
			);
		}

		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':username', $this->username );
		$statement->bindValue( ':email', $this->email );
		$statement->bindValue( ':password', password_hash( $this->password, PASSWORD_BCRYPT ) );
		$statement->bindValue( ':role', $this->role );
		$statement->execute();

		send_response_and_exit( 200, 'success', 'User saved.' );
	}


	/**
	 * Delete user.
	 */
	public function delete() {
		$statement = $this->db->prepare(
			'DELETE FROM "users"
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->execute();
		send_response_and_exit( 200, 'success', 'User deleted.' );
	}


	/**
	 * Chech if the current user is admin or not.
	 */
	public function is_admin() {
		return true; // TODO.
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
			send_response_and_exit( 200, 'success', 'Logged in.' );

		} else {
			throw new \Exception( 'Unauthorized.' );
		}

		exit; // All done.
	}


	/**
	 * Check if user is or should be logged in by validating the token data in cookie.
	 */
	public function is_logged_in() {

		$cookie_value    = isset( $_COOKIE['HSA_TOKEN'] ) ? $_COOKIE['HSA_TOKEN'] : '';
		$splitted_cookie = explode( '_', $cookie_value, 2 );
		$user_id         = $splitted_cookie[0];
		$token           = $splitted_cookie[1];

		$token_data  = openssl_decrypt(
			$token,
			$this->cipher,
			$_ENV['APP_SECRET_KEY'], // A secret key from enviroment vars.
			$options = 0,
			$this->get_init_vector( $user_id )  // User specific IV from the database.
		);
		$token_data = json_decode( $token_data, true );

		$is_valid_user = isset( $token_data['user_id'] )
						&& intval( $token_data['user_id'] ) === intval( $user_id );
		$cookietime    = isset( $token_data['timestamp'] ) ? intval( $token_data['timestamp'] ) : 0;
		$timenow       = intval( time() );
		$is_valid_time = ( $timenow - $cookietime ) < 3600; // Max login time is 1 hour.

		// new Logger()->write( $user_id );
		// new Logger()->write( $token );
		// new Logger()->write( $token_data );
		// new Logger()->write( $token );

		if ( $is_valid_user && $is_valid_time ) {
			$this->id = $user_id;
			return true;
		}

		return false;
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

			return $token;
		} else {
			throw new \Exception( 'Error Processing Request' );
		}
	}


	/**
	 * Save initialization vector the database among other user data
	 * to be used later on token validation process.
	 *
	 * @param string $iv The initialization vector.
	 */
	public function save_init_vector( $iv ) {
		// Use base 64 encoding to make sure
		// IV bytes remains unchanged during database operations.
		$iv        = base64_encode( $iv ); // phpcs:ignore
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
	public function get_init_vector( $user_id ) {
		$statement = $this->db->prepare(
			'SELECT iv FROM users
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $user_id );
		$results   = $statement->execute();
		$user_data = $results->fetchArray( SQLITE3_ASSOC );

		return isset( $user_data['iv'] ) ? base64_decode( $user_data['iv'] ) : ''; // phpcs:ignore
		// return isset( $user_data['iv'] ) ? $user_data['iv'] : ''; // phpcs:ignore
	}


	/**
	 * Logout
	 */
	public function logout() {
		setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.
		send_response_and_exit( 200, 'success', 'Logged out.' );
	}
}
