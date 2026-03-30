<?php
/**
 * The App class
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// require_once SRC_DIR . 'phpmailer/src/Exception.php';
// require_once SRC_DIR . 'phpmailer/src/PHPMailer.php';
// require_once SRC_DIR . 'phpmailer/src/SMTP.php';

require_once SRC_DIR . 'mailer.php';
require_once SRC_DIR . 'user.php';

/**
 * Class App
 */
final class App {

	private $db;
	public $user;
	public $logger;
	public $mailer;

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

			// Create tables.
			$this->db->query(
				'CREATE TABLE IF NOT EXISTS "users" (
					"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					"username" VARCHAR,
					"email" VARCHAR,
					"password" VARCHAR,
					"role" VARCHAR,
					"iv" VARCHAR,
					"otp" int,
					"otp_timestamp" VARCHAR,
					"pw_reset_key" VARCHAR,
					"pw_reset_timestamp" VARCHAR
				)'
			);

			$this->db->query(
				'CREATE TABLE IF NOT EXISTS "tasks" (
					"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					"name" VARCHAR,
					"description" VARCHAR,
					"status" VARCHAR
				)'
			);

			$this->db->query(
				'CREATE TABLE IF NOT EXISTS "users_tasks" (
					"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					"user_id" VARCHAR,
					"task_id" VARCHAR,
					"role" VARCHAR,
					FOREIGN KEY(user_id) REFERENCES users(id),
					FOREIGN KEY(task_id) REFERENCES tasks(id)
				)'
			);

			$this->db->query(
				'CREATE TABLE IF NOT EXISTS "settings" (
					"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					"name" VARCHAR,
					"label" VARCHAR,
					"value" VARCHAR
				)'
			);

			// Set up settings data if not alreay set.
			$results       = $this->db->query( 'SELECT * FROM "settings"' );
			$settings_data = $results->fetchArray();
			if ( ! $settings_data ) {
					$this->db->query(
						'INSERT INTO "settings"
						(name, label, value)
						VALUES 
						("smtp_host", "Mailer SMTP host", ""),
						("smtp_username", "Mailer SMTP username", ""),
						("smtp_password", "Mailer SMTP password", ""),
						("smtp_port", "Mailer SMTP port", ""),
						("smtp_sender", "Mailer sender email", "")
						'
					);
			}

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
		$this->mailer = new Mailer();
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

		try {

			if ( isset( $post_data->id ) ) {
				send_response_and_exit( 403, 'forbidden', 'Ambiguous data.' );
			}
			if ( isset( $post_data->email ) && $this->get_user_id_by_email( $post_data->email ) ) {
				send_response_and_exit( 403, 'forbidden', 'Try another email.' );
			}
			if ( isset( $post_data->username ) && $this->get_user_id_by_username( $post_data->username ) ) {
				send_response_and_exit( 403, 'forbidden', 'Try another username.' );
			}

			$this->save_user( $post_data );

		} catch ( \Throwable $exception ) {
			send_response_and_exit( 403, 'forbidden', $exception->getMessage() );
		}
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
			$password = isset( $post_data->password ) && is_valid_password( $post_data->password ) ? $post_data->password : '';
			$role     = isset( $post_data->role ) ? sanitize_role( $post_data->role ) : '';

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
			return $data['id'];
		} else {
			return false;
		}
	}

	/**
	 * Trigger a reset password process, if user found on the system.
	 *
	 * @param Object $post_data The post data containing the email address of the user whose password we want to reset.
	 */
	public function maybe_send_reset_password_email( $post_data ) {

		$email   = $post_data->email;
		$user_id = $this->get_user_id_by_email( $email );

		if ( $user_id ) {
			$user         = new User( $user_id );
			$timestamp    = time();
			$pw_reset_key = generate_random_string();

			$user->save_pw_reset_key( $pw_reset_key, $timestamp );

			$this->mailer->send_reset_password_email( $email, $pw_reset_key );
		}

		send_response_and_exit( 200, 'success', 'The reset password email has been sent.' );
	}


	/**
	 * Trigger a reset password process, if user found on the system.
	 *
	 * @param Object $post_data The post data containing the email address of the user whose password we want to reset.
	 */
	public function maybe_reset_password( $post_data ) {

		try {

			$pw_reset_key = $post_data->pw_reset_key;
			$new_password = isset( $post_data->password ) && is_valid_password( $post_data->password ) ? $post_data->password : '';
			$username     = $post_data->username;
			$user_id      = $this->get_user_id_by_username( $username );

		} catch ( \Throwable $exception ) {
			send_response_and_exit( 403, 'forbidden', $exception->getMessage() );
		}

		if ( $user_id && $pw_reset_key && $new_password ) {

			$user = new User( $user_id );
			$user->reset_password( $new_password, $pw_reset_key );
			send_response_and_exit( 200, 'success', 'New password saved.' );
		}

		send_response_and_exit( 403, 'forbidden', 'Missing data.' );
	}


	/**
	 * Save all settings.
	 *
	 * @param Object $post_data The post data containing the setting data.
	 */
	public function save_all_settings( $post_data ) {

		if ( ! $this->user->is_admin() ) {
			send_response_and_exit( 401, 'error', 'Unauthorized.' );
		}

		foreach ( $post_data->settings as $setting ) {
			$this->save_setting( $setting->name, $setting->value );
		}

		send_response_and_exit( 200, 'success', 'Settings saved.' );
	}


	/**
	 * Save a single setting.
	 *
	 * @param string     $name the name of the setting.
	 * @param string|int $value the option value.
	 */
	public function save_setting( $name, $value ) {

		$name  = sanitize_str( $name );
		$value = sanitize_str( $value );

		$statement = $this->db->prepare(
			'UPDATE settings
			SET value = :value
			WHERE name = :name'
		);
		$statement->bindValue( ':value', $value );
		$statement->bindValue( ':name', $name );
		$statement->execute();
	}

	/**
	 * Return settings data for admin users.
	 */
	public function return_all_settings() {

		if ( ! $this->user->is_admin() ) {
			send_response_and_exit( 401, 'error', 'Unauthorized.' );
		}
		send_response_and_exit( 200, 'success', $this->get_all_settings() );
	}

	/**
	 * Get all settings.
	 */
	public function get_all_settings() {
		$results_arr = array();
		$results     = $this->db->query( 'SELECT * FROM settings' );
		while ( $result = $results->fetchArray( SQLITE3_ASSOC ) ) {
			$results_arr[] = $result;
		}
		// new Logger()->write( $results_arr );
		return $results_arr;
	}
}
