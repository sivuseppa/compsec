<?php

namespace SweetHomeApp;

use PHPUnit\Framework\TestCase;
use SQLite3;

define( 'SRC_DIR', dirname( __DIR__, 1 ) . '/src/' );
define( 'DATA_DIR', dirname( __DIR__, 1 ) . '/data/' );
require_once dirname( __DIR__, 1 ) . '/src/user.php';

final class UserTest extends TestCase {

	// Tests will go here...

	public function testSaveAndGetInitVector() {
		$user     = new User();
		$user->id = 1;

		$ivlen       = openssl_cipher_iv_length( $user->cipher );
		$init_vector = openssl_random_pseudo_bytes( $ivlen );

		$user->save_init_vector( $init_vector );
		$this->assertSame( $init_vector, $user->get_init_vector() );
	}
}
