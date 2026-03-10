<?php
/**
 * The App class
 *
 * @package SweetHomeApp
 */

// namespace Api / DB;

require_once BACKEND_ROOT . '/includes/models/user.php';

/**
 * Class App
 */
final class App {

	private $db;
	public $user;

	public function __construct() {
		$this->init_db();
		$this->init_user();
	}

	private function init_db() {

		try {

			$this->db = new SQLite3( BACKEND_ROOT . '/includes/db/db.sqlite' );

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

			// Add an admin user for development purposes, if not found already.

			$results   = $this->db->query( 'SELECT * FROM "users" WHERE "username" = "admin"' );
			$user_data = $results->fetchArray();

			if ( ! $user_data ) {
				$statement = $this->db->prepare(
					'INSERT INTO "users" ("id", "username", "password", "role")
    				VALUES (:id, :username, :password, :role)'
				);
				$statement->bindValue( ':id', null );
				$statement->bindValue( ':username', 'admin' );
				$statement->bindValue( ':password', password_hash( 'admin', PASSWORD_BCRYPT ) );
				$statement->bindValue( ':role', 'admin' );
				$statement->execute();
			}
		} catch ( Exception $exception ) {
			error_log( 'DB Error: ' . $exception, 3, BACKEND_ROOT . '/includes/error.log' );
			exit;
		}
	}

	/**
	 * Init user. Try to find out if the user has an account and is logged in.
	 */
	private function init_user() {
		$this->user = new User();
	}


	/**
	 * Add a new user
	 */
	public function add_user() {
		$this->user->add();
	}
}
