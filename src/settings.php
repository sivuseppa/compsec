<?php
/**
 * Handle settings
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;

/**
 * Class Auth
 */
final class Settings {

	private $db;
	private $logger;
	private $mailer;

	public function __construct() {
		$this->db = new SQLite3( DATABASE );
		$this->db->enableExceptions( true );
		$this->logger = new Logger();
		$this->mailer = new Mailer();
	}

	/**
	 * Get all settings.
	 */
	public function get_all() {
		$results_arr = array();
		$results     = $this->db->query( 'SELECT * FROM settings' );
		while ( $result = $results->fetchArray( SQLITE3_ASSOC ) ) {
			$results_arr[] = $result;
		}
		// $this->logger->write( $results_arr );
		// new Logger()->write( $results_arr );
		return $results_arr;
	}

	/**
	 * Save a single setting.
	 *
	 * @param string     $name the name of the setting.
	 * @param string|int $value the option value.
	 */
	public function save_single( $name, $value ) {

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
	 * Save all settings.
	 *
	 * @param Object $post_data The post data containing the setting data.
	 */
	public function save_multiple( $post_data ) {

		foreach ( $post_data->settings as $setting ) {
			$this->save_single( $setting->name, $setting->value );
		}

		send_response_and_exit( 200, 'success', 'Settings saved.' );
	}
}
