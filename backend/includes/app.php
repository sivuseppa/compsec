<?php
/**
 * The App class
 *
 * @package SweetHomeApp
 */

// namespace Api / DB;

require_once BACKEND_ROOT . '/includes/models/user.php';
require_once BACKEND_ROOT . '/includes/dotenv/dotenv.php';

( new DotEnv( BACKEND_ROOT . '/includes/.env' ) )->load();


/**
 * Class App
 */
final class App {

	private $db;
	private $user;

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
					"role" VARCHAR
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

		} catch ( Exception $exception ) {
			error_log( 'Error: ' . $exception );
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
	 * Log in
	 *
	 * @param string $username name of the user.
	 * @param string $password password to verify.
	 */
	public function login( $username, $password ) {

		try {
			$statement = $this->db->prepare( 'SELECT "password" FROM "users" WHERE "username" = ?' );
			$statement->bindValue( $username );

			$hash         = $statement->execute();
			$is_logged_in = password_verify( $password, $hash );

			if ( $is_logged_in ) {
				setcookie( 'hsa_token', $username . ':is_logged_in', time() + 3600 );
				header( 'Content-Type: application/json' );
				echo json_encode(
					array(
						'status' => 'success',
						'data'   => 'User logged in.',
					)
				);

			} else {
				http_response_code( 402 );
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'unauthorized',
					)
				);
				exit;
			}
		} catch ( Exception $exception ) {
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

	/**
	 * Add a new user
	 */
	public function add_user() {

		try {

			$json = file_get_contents( 'php://input' );
			$data = json_decode( $json );

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
			// $this->getUserByName( $username );
			echo json_encode(
				array(
					'status' => 'success',
					'data'   => 'User added?',
				)
			);

		} catch ( Exception $exception ) {
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
