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
class Mailer {
	public function __construct() {
		
	}
}
