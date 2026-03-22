<?php
/**
 * Backend API entry point
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

define( 'SRC_DIR', dirname( __DIR__, 2 ) . '/src/' );
define( 'DATA_DIR', dirname( __DIR__, 2 ) . '/data/' );
header( 'Content-Type: application/json' );

require_once SRC_DIR . 'functions.php';
require_once SRC_DIR . 'dotenv/dotenv.php';
require_once SRC_DIR . 'logger.php';
require_once SRC_DIR . 'app.php';

try {
	new DotEnv( dirname( __DIR__, 2 ) . '/.env' )->load(); // Load .env file data to $_ENV superglobal.

	$app    = new App();
	$method = $_SERVER['REQUEST_METHOD'];
	$data   = null;
	$action = null;

	// new Logger()->write( $app->get_users() );

	// Get action parameters and POST data.
	if ( 'POST' === $method ) {
		$json   = file_get_contents( 'php://input' );
		$data   = json_decode( $json );
		$action = isset( $data->action ) ? $data->action : '';

		// Login is only action allowed without authentication.
		if ( 'login' === $action ) {
			$app->user->login( $data );
		}
	} elseif ( 'GET' === $method ) {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
	}

	// Stop unauthenticated execution here.
	if ( ! $app->user->is_logged_in() ) {
		setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.
		send_response_and_exit( 401, 'error', 'unauthorized' );
	}

	// Call app methods based on an action parameter.
	switch ( $method ) {
		case 'GET':
			if ( 'getUser' === $action ) {
				$app->get_user();
			} elseif ( 'getUsers' === $action ) {
				$app->return_userdata();
			} else {
				throw new \Exception( 'Please check your action.' );
			}
			break;
		case 'POST':
			if ( 'logout' === $action ) {
				$app->user->logout();
			} elseif ( 'saveUser' === $action ) {
				$app->save_user( $data );
			} else {
				throw new \Exception( 'Please check your action.' );
			}
			break;
		default:
			throw new \Exception( 'Please check your method.' );
	}
} catch ( \Throwable $exception ) {
	new Logger()->write( $exception->getMessage() );
	// $app->user->logout();
	send_response_and_exit( 500, 'error', $exception->getMessage() );
}

send_response_and_exit( 401, '0', 'unauthorized' );
