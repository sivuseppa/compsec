<?php
/**
 * The App class
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;

require_once SRC_DIR . 'user.php';

/**
 * Class App
 */
final class App {

	private $db;
	public $user;
	public $logger;

	public function __construct() {
		$this->logger = new Logger();
		$this->init_db();
		$this->init_user();
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
	 * Return backend API description.
	 */
	function return_api_description() {
	}

	/**
	 * Return user data for admin users.
	 */
	public function return_userdata() {

		if ( ! $this->user->is_admin() ) {
			send_response_and_exit( 401, 'error', 'unauthorized' );
		}

		send_response_and_exit( 200, 'success', $this->get_users_data() );
	}

	/**
	 * Get all users from the database.
	 */
	private function get_users_data() {
		$results_arr = array();
		$results     = $this->db->query( 'SELECT id, username, role FROM "users"' );
		while ( $result = $results->fetchArray( SQLITE3_ASSOC ) ) {
			$results_arr[] = $result;
		}
		new Logger()->write( $results_arr );
		return $results_arr;
	}

	/**
	 * Save userdata or create new user.
	 */
	public function save_user( $post_data ) {

		$user_id  = isset( $post_data->id ) && $post_data->id ? intval( $post_data->id ) : null;
		$username = isset( $post_data->username ) ? $post_data->username : '';
		$password = isset( $post_data->password ) ? $post_data->password : '';
		$role     = isset( $post_data->role ) ? $post_data->role : '';

		if ( ! $username || ! $password || ! $role ) {
			send_response_and_exit( 403, 'forbidden', 'missing data' );
		}

		if ( 'admin' === $role && ! $this->user->is_admin() ) {
			send_response_and_exit( 401, 'error', 'unauthorized' );
		}

		if ( $user_id === $this->user->id || $this->user->is_admin() ) {

			$user           = new User( $user_id );
			$user->username = $username;
			$user->password = $password;
		}

		if ( $this->user->is_admin() ) {
			$user->role = $role;
		}

		$user->save();
	}
}
