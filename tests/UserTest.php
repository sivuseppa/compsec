<?php
/**
 * Tests for User
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use PHPUnit\Framework\TestCase;
use SQLite3;

define( 'SRC_DIR', dirname( __DIR__, 1 ) . '/src/' );
define( 'DATA_DIR', dirname( __DIR__, 1 ) . '/data/' );

require_once SRC_DIR . 'logger.php';
require_once dirname( __DIR__, 1 ) . '/src/user.php';

require_once SRC_DIR . 'dotenv/dotenv.php';
new DotEnv( dirname( __DIR__, 1 ) . '/.env' )->load(); // Load .env file data to $_ENV superglobal.


final class UserTest extends TestCase {

	/**
	 * Test #1
	 */
	public function testSaveAndGetInitVector() {
		$user        = new User( 1 );
		$ivlen       = openssl_cipher_iv_length( $user->cipher );
		$init_vector = openssl_random_pseudo_bytes( $ivlen );

		$user->save_init_vector( $init_vector );
		$this->assertSame( $init_vector, $user->get_init_vector() );
	}

	/**
	 * Test #2
	 */
	public function testCreateAndValidateToken() {
		$user  = new User( 1 );
		$time  = time();
		$token = $user->create_token( $time );

		$token_data  = openssl_decrypt(
			$token,
			$user->cipher,
			$_ENV['APP_SECRET_KEY'], // A secret key from enviroment vars.
			$options = 0,
			$user->get_init_vector()  // User specific IV from the database.
		);
		$token_data = json_decode( $token_data, true );

		$this->assertSame( 1, $token_data['user_id'] );
		$this->assertSame( $time, $token_data['timestamp'] );
	}
}
