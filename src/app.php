<?php
/**
 * The App class
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once SRC_DIR . 'phpmailer/src/Exception.php';
require_once SRC_DIR . 'phpmailer/src/PHPMailer.php';
require_once SRC_DIR . 'phpmailer/src/SMTP.php';
require_once SRC_DIR . 'user.php';

/**
 * Class App
 */
final class App {

	private $db;
	public $user;
	public $logger;
	public $mail;

	public function __construct() {
		$this->logger = new Logger();
		$this->init_db();
		$this->init_user();
		$this->init_mailer();
	}

	private function init_db() {

		try {
			$this->db = new SQLite3( DATA_DIR . 'db/db.sqlite' );

			// Errors are emitted as warnings by default, enable proper error handling.
			$this->db->enableExceptions( true );

			// Create a tables.
			$this->db->query(
				'CREATE TABLE IF NOT EXISTS "users" (
					"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					"username" VARCHAR,
					"email" VARCHAR,
					"password" VARCHAR,
					"role" VARCHAR,
					"iv" VARCHAR
				)'
			);

			$this->db->query(
				'CREATE TABLE IF NOT EXISTS "home_assistants" (
					"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					"name" VARCHAR,
					"url" VARCHAR,
					"api_key" VARCHAR
				)'
			);

			$app_env        = isset( $_ENV['APP_ENV'] ) ? $_ENV['APP_ENV'] : '';
			$admin_username = isset( $_ENV['ADMIN_UNAME'] ) ? $_ENV['ADMIN_UNAME'] : '';
			$admin_password = isset( $_ENV['ADMIN_PASS'] ) ? $_ENV['ADMIN_PASS'] : '';

			// On development enviroment,
			// add an admin user for development purposes.
			if ( 'dev' === $app_env && $admin_username && $admin_password ) {

				$results   = $this->db->query( 'SELECT * FROM "users" WHERE "username" = "admin"' );
				$user_data = $results->fetchArray();

				if ( ! $user_data ) {
					$statement = $this->db->prepare(
						'INSERT INTO "users" ("id", "username", "password", "role")
    					VALUES (:id, :username, :password, :role)'
					);
					$statement->bindValue( ':id', null );
					$statement->bindValue( ':username', $admin_username );
					$statement->bindValue( ':password', password_hash( $admin_password, PASSWORD_BCRYPT ) );
					$statement->bindValue( ':role', 'admin' );
					$statement->execute();
				}
			}
		} catch ( \Throwable $exception ) {
			$this->logger->write( 'DB Error: ' . $exception->getMessage() );
			exit;
		}
	}

	/**
	 * Init user.
	 */
	private function init_user() {
		$this->user = new User();
	}

	/**
	 * Init mailer
	 */
	private function init_mailer() {
		$this->mail = new PHPMailer( true );
		// Mail server settings.
		$this->mail->isSMTP();
		$this->mail->SMTPDebug  = 2;                                         // Send using SMTP
		$this->mail->Host       = $_ENV['SMTP_HOST'];                     // Set the SMTP server to send through
		$this->mail->SMTPAuth   = true;                                   // Enable SMTP authentication
		$this->mail->Username   = $_ENV['SMTP_USERNAME'];               // SMTP username
		$this->mail->Password   = $_ENV['SMTP_PASS'];                        // SMTP password
		$this->mail->SMTPSecure = 'tls'; // Enable TLS encryption
		$this->mail->Port       = $_ENV['SMTP_PORT'];
		$this->mail->Mailer     = 'smtp';
	}

	/**
	 * Return data of the current user.
	 */
	public function get_current_user() {
		send_response_and_exit( 200, 'success', $this->user->get_userdata() );
	}


	/**
	 * Return user data for admin users.
	 */
	public function return_userdata() {

		if ( ! $this->user->is_admin() ) {
			send_response_and_exit( 401, 'error', 'Unauthorized.' );
		}
		send_response_and_exit( 200, 'success', $this->get_users_data() );
	}

	/**
	 * Get all users from the database.
	 */
	private function get_users_data() {
		$results_arr = array();
		$results     = $this->db->query( 'SELECT id, username, email, role FROM "users"' );
		while ( $result = $results->fetchArray( SQLITE3_ASSOC ) ) {
			$results_arr[] = $result;
		}
		// new Logger()->write( $results_arr );
		return $results_arr;
	}

	/**
	 * Add new user to the system. Make sure that the post data is valid to use on user creation.
	 *
	 * @param object $post_data the post data.
	 */
	public function add_user( $post_data ) {

		if ( isset( $post_data->id ) ) {
			send_response_and_exit( 403, 'forbidden', 'Ambiguous data.' );
		}

		if ( isset( $post_data->email ) && $this->get_user_id_by_email( $post_data->email ) ) {
			send_response_and_exit( 403, 'forbidden', 'Wrong email.' );
		}

		if ( isset( $post_data->username ) && $this->get_user_id_by_username( $post_data->username ) ) {
			send_response_and_exit( 403, 'forbidden', 'Wrong username.' );
		}

		$this->save_user( $post_data );
	}

	/**
	 * Save userdata.
	 *
	 * @param object $post_data the post data.
	 */
	public function save_user( $post_data ) {

		try {

			$user_id  = isset( $post_data->id ) && intval( $post_data->id ) ? intval( $post_data->id ) : null;
			$username = isset( $post_data->username ) ? sanitize_str( $post_data->username ) : '';
			$email    = isset( $post_data->email ) ? sanitize_email( $post_data->email ) : '';
			$password = isset( $post_data->password ) ? validate_password( $post_data->password ) : '';
			$role     = isset( $post_data->role ) ? validate_role( $post_data->role ) : '';

		} catch ( \Throwable $exception ) {
			send_response_and_exit( 403, 'forbidden', $exception->getMessage() );
		}

		if ( ! $username || ! $password || ! $role || ! $email ) {
			send_response_and_exit( 403, 'forbidden', 'Missing data.' );
		}

		if ( 'admin' === $role && ! $this->user->is_admin() ) {
			send_response_and_exit( 401, 'error', 'Unauthorized.' );
		}

		// User can save only own data, admin can save others data too.
		if ( $user_id === $this->user->id || $this->user->is_admin() ) {

			$user           = new User( $user_id );
			$user->username = $username;
			$user->email    = $email;
			$user->password = $password;

			if ( $this->user->is_admin() ) {
				$user->role = $role;
			}

			$user->save();
		} else {
			send_response_and_exit( 401, 'error', 'Unauthorized.' );
		}
	}

	/**
	 * Delete user
	 *
	 * @param object $post_data the post data.
	 */
	public function delete_user( $post_data ) {
		$user_id = isset( $post_data->id ) && $post_data->id ? intval( $post_data->id ) : null;

		if ( ! $user_id ) {
			send_response_and_exit( 403, 'forbidden', 'Missing data.' );
		}

		if ( ! $this->user->is_admin() ) {
			send_response_and_exit( 401, 'error', 'Unauthorized.' );
		}

		$user = new User( $user_id );
		$user->delete();
	}

	/**
	 * Get user by email
	 *
	 * @param string $email the email fo the user.
	 * @return int|bool user id on success, false on fail.
	 */
	private function get_user_id_by_email( $email ) {
		$email = sanitize_email( $email );

		$statement = $this->db->prepare(
			'SELECT id FROM users
			WHERE email = :email'
		);
		$statement->bindValue( ':email', $email );
		$result = $statement->execute();
		$data   = $result->fetchArray( SQLITE3_ASSOC );

		$this->logger->write( $data );

		if ( isset( $data['id'] ) && $data['id'] ) {
			$this->logger->write( $data['id'] );
			return $data['id'];
		} else {
			return false;
		}
	}

	/**
	 * Get user by username
	 *
	 * @param string $username the username fo the user.
	 * @return int|bool user id on success, false on fail.
	 */
	private function get_user_id_by_username( $username ) {
		$username = sanitize_str( $username );

		$statement = $this->db->prepare(
			'SELECT id FROM users
			WHERE username = :username'
		);
		$statement->bindValue( ':username', $username );
		$result = $statement->execute();
		$data   = $result->fetchArray( SQLITE3_ASSOC );

		// $this->logger->write( $data );

		if ( isset( $data['id'] ) && $data['id'] ) {
			// $this->logger->write( $data['id'] );
			return $data['id'];
		} else {
			return false;
		}
	}
}
