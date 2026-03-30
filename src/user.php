<?php
/**
 * The model of the User
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;

require_once SRC_DIR . 'mailer.php';

/**
 * The User model class
 */
final class User {

	private $db;
	private $mailer;

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
		$this->db     = new SQLite3( DATA_DIR . 'db/db.sqlite' );
		$this->id     = $user_id;
		$this->mailer = new Mailer();
		// $this->get_userdata();
	}


	/**
	 * Return userdata.
	 */
	public function get_userdata() {

		if ( ! $this->username ) {
			$statement = $this->db->prepare( 'SELECT * FROM "users" WHERE "id" = ?' );
			$statement->bindValue( 1, $this->id );
			$result    = $statement->execute();
			$user_data = $result->fetchArray( SQLITE3_ASSOC );

			$this->username = $user_data['username'];
			$this->email    = $user_data['email'];
			$this->role     = $user_data['role'];
		}

		return array(
			'id'       => $this->id,
			'username' => $this->username,
			'email'    => $this->email,
			'role'     => $this->role,
		);
	}

	/**
	 * Return email.
	 */
	public function get_email() {
		if ( $this->email ) {
			return $this->email;
		}

		$statement = $this->db->prepare( 'SELECT email FROM users WHERE id = ?' );
		$statement->bindValue( 1, $this->id );
		$result    = $statement->execute();
		$user_data = $result->fetchArray( SQLITE3_ASSOC );

		return isset( $user_data['email'] ) ? $user_data['email'] : '';
	}


	/**
	 * Save userdata to the database.
	 */
	public function save() {

		// Insert potentially unsafe data with a prepared statement and named parameters.
		if ( $this->id ) {
			$statement = $this->db->prepare(
				'UPDATE users 
				SET username = :username, email = :email, role = :role
				WHERE id = :id'
			);
		} else {
			$statement = $this->db->prepare(
				'INSERT INTO users ("id", email, "username", "role")
				VALUES (:id, :email, :username, :role)'
			);
		}

		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':username', $this->username );
		$statement->bindValue( ':email', $this->email );
		$statement->bindValue( ':role', $this->role );
		$statement->execute();

		// Test how userdata is saved, remove this!!!
		// $statement = $this->db->prepare( 'SELECT * FROM "users" WHERE "id" = ?' );
		// $statement->bindValue( 1, $this->id );
		// $result    = $statement->execute();
		// $user_data = $result->fetchArray( SQLITE3_ASSOC );
		// new Logger()->write( $user_data );

		if ( $this->password ) {
			$this->save_password( $this->password );
		}

		send_response_and_exit( 200, 'success', 'User saved.' );
	}

	/**
	 * Save new password to the database.
	 *
	 * @param string $new_password The new password.
	 */
	public function save_password( $new_password ) {
		$statement = $this->db->prepare(
			'UPDATE users 
			SET password = :password
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':password', password_hash( $new_password, PASSWORD_BCRYPT ) );
		$statement->execute();
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
			$this->get_init_vector( $user_id )  // User specific IV from the database.
		);
		$token_data = json_decode( $token_data, true );

		$is_valid_user = isset( $token_data['user_id'] )
						&& intval( $token_data['user_id'] ) === intval( $user_id );
		$cookietime    = isset( $token_data['timestamp'] ) ? intval( $token_data['timestamp'] ) : 0;
		$timenow       = intval( time() );
		$is_valid_time = ( $timenow - $cookietime ) < 3600; // Max login time is 1 hour.

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
	}


	/**
	 * Reset password.
	 *
	 * @param string $new_password The new password.
	 * @param string $pw_reset_key The reset key to authorize the action.
	 */
	public function reset_password( $new_password, $pw_reset_key ) {

		if ( ! $this->is_valid_pw_reset_key( $pw_reset_key ) ) {
			send_response_and_exit( 401, 'error', 'Unauthorized, please request a new password reset link.' );
		}

		$this->save_password( $new_password );
	}


	/**
	 * Save a password reset key to the database among other user data.
	 *
	 * @param string $key The sercret key which allow reset password.
	 * @param string $timestamp The time to handle reset key expiration.
	 */
	public function save_pw_reset_key( $key, $timestamp ) {
		$statement = $this->db->prepare(
			'UPDATE users SET pw_reset_key = :pw_reset_key, pw_reset_timestamp = :pw_reset_timestamp
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':pw_reset_key', $key );
		$statement->bindValue( ':pw_reset_timestamp', $timestamp );
		$statement->execute();
	}

	/**
	 * Validate the password reset key.
	 *
	 * @param string $_pw_reset_key The reset key to validate.
	 */
	public function is_valid_pw_reset_key( $_pw_reset_key ) {
		$statement = $this->db->prepare(
			'SELECT pw_reset_key, pw_reset_timestamp FROM users
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$results       = $statement->execute();
		$pw_reset_data = $results->fetchArray( SQLITE3_ASSOC );

		$pw_reset_key       = isset( $pw_reset_data['pw_reset_key'] ) ? $pw_reset_data['pw_reset_key'] : '';
		$pw_reset_timestamp = isset( $pw_reset_data['pw_reset_timestamp'] ) ? intval( $pw_reset_data['pw_reset_timestamp'] ) : 0;
		$timenow            = intval( time() );

		$is_valid_key  = $pw_reset_key === $_pw_reset_key;
		$is_valid_time = ( $timenow - $pw_reset_timestamp ) < 300; // Max valid time is 5 min.

		if ( $is_valid_time && $is_valid_key ) {
			return true;
		}

		return false;
	}


	/**
	 * Logout
	 */
	public function logout() {
		setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.
		send_response_and_exit( 200, 'success', 'Logged out.' );
	}
}
