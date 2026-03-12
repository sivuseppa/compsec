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
	 * Init user. Try to find out if the user has an account and is logged in.
	 */
	private function init_user() {
		$this->user = new User();
	}
}
