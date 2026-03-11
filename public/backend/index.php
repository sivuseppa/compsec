<?php
/**
 * Backend API entry point
 *
 * @package SweetHomeApp
 */

namespace SweetHomeApp;

define( 'SRC_DIR', dirname( __DIR__, 2 ) . '/src/' );
define( 'DATA_DIR', dirname( __DIR__, 2 ) . '/data/' );
header( 'Content-Type: application/json' );

require_once SRC_DIR . 'dotenv/dotenv.php';
new DotEnv( DATA_DIR . '.env' )->load(); // Load .env file data to $_ENV superglobal.

require_once SRC_DIR . 'logger.php';
require_once SRC_DIR . 'app.php';

try {
	$app = new App();

	$request_method = $_SERVER['REQUEST_METHOD'];

	$data   = null;
	$action = null;

	// Get action parameters and POST data.
	if ( 'POST' === $request_method ) {
		$json   = file_get_contents( 'php://input' );
		$data   = json_decode( $json );
		$action = isset( $data->action ) ? $data->action : '';

		// Login or Create account are only actions allowed without authentication.
		if ( 'login' === $action ) {
			$app->user->login( $data );
		}
	} elseif ( 'GET' === $request_method ) {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
	}

	// Stop unauthenticated execution here.
	if ( ! $app->user->is_logged_in() ) {
		setcookie( 'HSA_TOKEN', '', 0, '/' ); // Set outdated timestamp to remove cookie.
		http_response_code( 401 );
		echo json_encode(
			array(
				'status'  => 'error 1',
				'message' => 'unauthorized',
			)
		);
		exit;
	}

	// Call app methods based on a action parameter.
	switch ( $request_method ) {
		case 'GET':
			if ( 'getUser' === $action ) {
				$app->get_user();
			} elseif ( 'getAssistant' === $action ) {
				$app->get_assistant();
			} elseif ( 'getAllAssistants' === $action ) {
				$app->get_all_assistants();
			}
			break;
		case 'POST':
			if ( 'logout' === $action ) {
				$app->user->logout();
			} elseif ( 'addUser' === $action ) {
				$app->user->add( $data );
			} elseif ( 'editUser' === $action ) {
				$app->edit_user();
			} elseif ( 'addAssistant' === $action ) {
				$app->add_assistant();
			} elseif ( 'editAssistant' === $action ) {
				$app->edit_assistant();
			}
			break;
		default:
			break;
	}
} catch ( \Throwable $exception ) {
	new Logger()->write( $exception->getMessage() );
	http_response_code( 500 );
	echo json_encode(
		array(
			'status'  => 'error',
			'message' => '#1',
		)
	);
	exit;
}
