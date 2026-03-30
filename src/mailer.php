<?php
/**
 * The Mailer Class, uses PHPMailer
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once SRC_DIR . 'phpmailer/src/Exception.php';
require_once SRC_DIR . 'phpmailer/src/PHPMailer.php';
require_once SRC_DIR . 'phpmailer/src/SMTP.php';

/**
 * Class Mailer
 */
final class Mailer {

	public $mail;

	public function __construct() {
		$this->mail = new PHPMailer( true );
		$this->init_mailer();
	}

	private function init_mailer() {
		// Mail server settings.
		$this->mail->isSMTP();
		// $this->mail->SMTPDebug  = 2;
		$this->mail->Host       = $_ENV['SMTP_HOST'];
		$this->mail->SMTPAuth   = true;
		$this->mail->Username   = $_ENV['SMTP_USERNAME'];
		$this->mail->Password   = $_ENV['SMTP_PASS'];
		$this->mail->SMTPSecure = 'tls';
		$this->mail->Port       = $_ENV['SMTP_PORT'];
		$this->mail->Mailer     = 'smtp';

		// Message settings.
		$this->mail->isHTML( true );

		// Mail sender settings.
		$this->mail->setFrom( $_ENV['SMTP_SENDER'], 'CompSecApp' );
	}

	public function send_test_email( $email ) {
		$this->mail->addAddress( $email, '' );
		$this->mail->Subject = 'Test email from CompSecApp';
		$this->mail->Body    = 'This is a test email from yout CompSecApp.';
		$this->mail->send();
	}

	public function send_2fa_email( $email, $one_time_password ) {
	}

	public function send_reset_password_email( $email, $pw_reset_key ) {

		$pw_reset_address = 'https://' . $_ENV['APP_DOMAIN'] . '/?resetPassword=' . $pw_reset_key;

		$this->mail->addAddress( $email, '' );
		$this->mail->Subject = 'Reset password';
		$this->mail->Body    = 'Reset your password using an address below: <br>' . $pw_reset_address;
		$this->mail->send();
	}
}
