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

	private static $db;

	public $id = null;
	public $username;
	public $password;
	public $email;
	public $role = '';

	/**
	 * Constuctor
	 *
	 * @param int $user_id The id of the user.
	 */
	public function __construct( $user_id = null ) {
		$this->id = $user_id;
	}

	public static function set_db( $db ) {
		self::$db = $db;
	}

	/**
	 * Return userdata.
	 */
	public function get_userdata() {

		if ( ! $this->username ) {
			$statement = self::$db->prepare( 'SELECT * FROM "users" WHERE "id" = ?' );
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
	 * Get all users from the database.
	 */
	public function get_users_data() {
		$results_arr = array();
		$results     = self::$db->query( 'SELECT id, username, email, role FROM "users"' );
		while ( $result = $results->fetchArray( SQLITE3_ASSOC ) ) {
			$results_arr[] = $result;
		}
		// new Logger()->write( $results_arr );
		return $results_arr;
	}

	/**
	 * Return email.
	 */
	public function get_email() {
		if ( $this->email ) {
			return $this->email;
		}

		$statement = self::$db->prepare( 'SELECT email FROM users WHERE id = ?' );
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
			$statement = self::$db->prepare(
				'UPDATE users 
				SET username = :username, email = :email, role = :role
				WHERE id = :id'
			);
		} else {
			$statement = self::$db->prepare(
				'INSERT INTO users ("id", email, "username", "role")
				VALUES (:id, :email, :username, :role)'
			);
		}

		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':username', $this->username );
		$statement->bindValue( ':email', $this->email );
		$statement->bindValue( ':role', $this->role );
		$statement->execute();

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

		$statement = self::$db->prepare(
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
		$statement = self::$db->prepare(
			'DELETE FROM "users"
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->execute();
		send_response_and_exit( 200, 'success', 'User deleted.' );
	}


	/**
	 * Check if the current user is admin or not.
	 */
	public function is_admin() {

		$user_data = $this->get_userdata();
		$user_role = isset( $user_data['role'] ) ? $user_data['role'] : '';

		if ( 'admin' === $user_role ) {
			return true;
		} else {
			return false;
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
		$statement = self::$db->prepare(
			'UPDATE users SET iv = :iv
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':iv', $iv );
		$statement->execute();
		$statement->close();
	}

	/**
	 * Return initialization vector from the database.
	 */
	public function get_init_vector( $user_id ) {
		$statement = self::$db->prepare(
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
		$statement = self::$db->prepare(
			'UPDATE users SET pw_reset_key = :pw_reset_key, pw_reset_timestamp = :pw_reset_timestamp
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':pw_reset_key', $key );
		$statement->bindValue( ':pw_reset_timestamp', $timestamp );
		$statement->execute();
		$statement->close();
	}

	/**
	 * Validate the password reset key.
	 *
	 * @param string $_pw_reset_key The reset key to validate.
	 */
	public function is_valid_pw_reset_key( $_pw_reset_key ) {
		$statement = self::$db->prepare(
			'SELECT pw_reset_key, pw_reset_timestamp FROM users
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$results       = $statement->execute();
		$pw_reset_data = $results->fetchArray( SQLITE3_ASSOC );

		$pw_reset_key       = isset( $pw_reset_data['pw_reset_key'] ) ? $pw_reset_data['pw_reset_key'] : '';
		$pw_reset_timestamp = isset( $pw_reset_data['pw_reset_timestamp'] ) ? intval( $pw_reset_data['pw_reset_timestamp'] ) : 0;
		$timenow            = intval( time() );

		$is_valid_key  = hash_equals( $pw_reset_key, $_pw_reset_key );
		$is_valid_time = ( $timenow - $pw_reset_timestamp ) < 300; // Max valid time is 5 min.

		if ( $is_valid_time && $is_valid_key ) {
			return true;
		}

		return false;
	}
}
