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

	private static $db;
	private $logger;
	private $mailer;
	private $cipher = 'aes-256-cbc';

	public function __construct() {
		$this->logger = new Logger();
		$this->mailer = new Mailer();
	}

	public static function set_db( $db ) {
		self::$db = $db;
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

		$statement = self::$db->prepare( 'SELECT * FROM "users" WHERE "username" = ?' );
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
			$time     = time(); // define time variable here to ease testing.

			$options = array(
				'expires'  => $time + 3600,
				'path'     => '/',
				'secure'   => true,
				'httponly' => true,
				'samesite' => 'Strict',
			);
			setcookie(
				'HSA_TOKEN',
				$this->create_token( $user, $time ),
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

		$cookie_value = isset( $_COOKIE['HSA_TOKEN'] ) ? $_COOKIE['HSA_TOKEN'] : '';

		// Remove session from the database.
		$statement = self::$db->prepare(
			'DELETE FROM "sessions"
			WHERE token = :token'
		);
		$statement->bindValue( ':token', $cookie_value );
		$statement->execute();

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

		// Find session data by cookie.
		$statement = self::$db->prepare( 'SELECT * FROM "sessions" WHERE "token" = ?' );
		$statement->bindValue( 1, $cookie_value );
		$result       = $statement->execute();
		$session_data = $result->fetchArray( SQLITE3_ASSOC );
		$result->finalize();

		if ( ! $session_data ) {
			return false;
		}

		// Check that user still exists in the database.
		$statement = self::$db->prepare( 'SELECT * FROM "users" WHERE "id" = ?' );
		$statement->bindValue( 1, intval( $session_data['user_id'] ) );
		$result    = $statement->execute();
		$user_data = $result->fetchArray( SQLITE3_ASSOC );
		$result->finalize();

		if ( ! $user_data ) {
			return false;
		}

		// Check if the session is still valid by the time.
		$cookietime    = isset( $session_data['timestamp'] ) ? intval( $session_data['timestamp'] ) : 0;
		$timenow       = intval( time() );
		$is_valid_time = ( $timenow - $cookietime ) < 3600; // Max login time is 1 hour.

		if ( $is_valid_time ) {
			// If everything is ok, let user in.
			$user->id = $session_data['user_id'];
			return true;
		}

		return false;
	}


	/**
	 * Create a cryptographically secure token for login cookie.
	 *
	 * @param Object $user The user.
	 * @param string $time The timestamp.
	 */
	public function create_token( $user, $time ) {

		$token = bin2hex( random_bytes( 32 ) );

		$statement = self::$db->prepare(
			'INSERT INTO "sessions" ("id", "user_id", "token", "timestamp")
			VALUES (:id, :user_id, :token, :timestamp)'
		);
		$statement->bindValue( ':id', null );
		$statement->bindValue( ':user_id', $user->id );
		$statement->bindValue( ':token', $token );
		$statement->bindValue( ':timestamp', $time );
		$statement->execute();

		return $token;
	}
}
