<?php
/**
 * Backend API entry point
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

define( 'SRC_DIR', dirname( __DIR__, 2 ) . '/src/' );
define( 'DATA_DIR', dirname( __DIR__, 2 ) . '/data/' );
define( 'DATABASE', DATA_DIR . 'db/db.sqlite' );
define( 'APP_VERSION', '0.1.0' );
header( 'Content-Type: application/json' );

require_once SRC_DIR . 'functions.php';
require_once SRC_DIR . 'dotenv/dotenv.php';
require_once SRC_DIR . 'logger.php';
require_once SRC_DIR . 'app.php';

( function () {
	// Prevent global scoping. Why?

	try {
		new DotEnv( dirname( __DIR__, 2 ) . '/.env' )->load(); // Load .env file data to $_ENV superglobal.

		$app    = new App();
		$method = $_SERVER['REQUEST_METHOD'];
		$data   = null;
		$action = null;

		// Get action parameters and POST data.
		if ( 'POST' === $method ) {
			$json   = file_get_contents( 'php://input' );
			$data   = json_decode( $json );
			$action = isset( $data->action ) ? $data->action : '';

			new Logger()->write( $data );

			// Login and resetPassword are only actions allowed without authentication.
			if ( 'login' === $action ) {
				$app->log_user_in( $data );
			} elseif ( 'lostPassword' === $action ) {
				$app->maybe_send_reset_password_email( $data );
			} elseif ( 'resetPassword' === $action ) {
				$app->maybe_reset_password( $data );
			}
		} elseif ( 'GET' === $method ) {
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		}

		// Stop unauthenticated execution here.
		if ( ! $app->is_user_logged_in() ) {
			setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.
			send_response_and_exit( 401, 'error', 'Unauthorized.' );
		}

		// Call app methods based on an action parameter.
		switch ( $method ) {
			case 'GET':
				if ( 'getCurrentUser' === $action ) {
					$app->return_current_user();
				} elseif ( 'getUsers' === $action ) {
					$app->return_users();
				} elseif ( 'getTasks' === $action ) {
					$app->return_tasks();
				} elseif ( 'getSettings' === $action ) {
					$app->return_all_settings();
				} else {
					throw new \Exception( 'Please check your action.' );
				}
				break;
			case 'POST':
				if ( 'logout' === $action ) {
					$app->log_user_out();
				} elseif ( 'addUser' === $action ) {
					$app->add_new_user( $data );
				} elseif ( 'saveUser' === $action ) {
					$app->save_user( $data );
				} elseif ( 'deleteUser' === $action ) {
					$app->delete_user( $data );
				} elseif ( 'addTask' === $action ) {
					$app->add_new_task( $data );
				} elseif ( 'saveSettings' === $action ) {
					$app->save_all_settings( $data );
				} else {
					throw new \Exception( 'Please check your action.' );
				}
				break;
			default:
				throw new \Exception( 'Please check your method.' );
		}
	} catch ( \Throwable $exception ) {
		new Logger()->write( $exception->getMessage() );
		send_response_and_exit( 500, 'error', 'System error.' );
	}
} )();

send_response_and_exit( 401, 'error', 'Unauthorized.' );
