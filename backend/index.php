<?php
/**
 * API entry point
 *
 * @package SweetHomeApp
 */

define( 'BACKEND_ROOT', dirname( __FILE__ ) );

require_once BACKEND_ROOT . '/includes/app.php';

$app = new App();

$requestUri    = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ( $requestMethod ) {
	case 'GET':
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		if ( 'getUser' === $action ) {
			$app->get_user();
		} elseif ( 'getAssistant' === $action ) {
			$app->get_assistant();
		} elseif ( 'getAllAssistants' === $action ) {
			$app->get_all_assistants();
		}
		break;
	case 'POST':
		// Get and decode the JSON contents.
		$json   = file_get_contents( 'php://input' );
		$data   = json_decode( $json );
		$action = isset( $data->action ) ? $data->action : '';

		if ( 'login' === $action ) {
			$app->login();
		} elseif ( 'addUser' === $action ) {
			$app->add_user();
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
