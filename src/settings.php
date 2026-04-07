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

	private static $db;
	// private $db;
	private $logger;
	private $mailer;

	public function __construct() {
		$this->logger = new Logger();
		$this->mailer = new Mailer();
	}

	public static function set_db( $db ) {
		self::$db = $db;
	}

	/**
	 * Get all settings.
	 */
	public function get_all() {
		$results_arr = array();
		$results     = self::$db->query( 'SELECT * FROM settings' );
		while ( $result = $results->fetchArray( SQLITE3_ASSOC ) ) {
			$results_arr[] = $result;
		}
		// $this->logger->write( $results_arr );
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

		$statement = self::$db->prepare(
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
