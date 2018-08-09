<?php
require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
# here, if something is called, we get the post vars and we update the user signature
# then we return json status of the operation, along with any message
//
use PHPMailer\PHPMailer\PHPMailer;


try {
	if (!isset($_POST['email'])) {
		throw new Exception("Email needs to be in the post");
	}

	if (!isset($_POST['sig'])) {
		throw new Exception("New Signature needs to be in the post");
	}

	if (!isset($_POST['alias'])) {
		throw new Exception("Alias needs to be in the post");
	}

	$primary_email = $_POST['email'];
	$alias = $_POST['alias'];
	$sig_base_64 = $_POST['sig'];
	$sig = base64_decode($sig_base_64);
	if (!$sig) {
		throw new Exception("could not unencode base64 signature");
	}

	$root_path  = realpath(dirname(__FILE__));
	$email_message = file_get_contents( $root_path . "/templates/email_notice_template.php" );
	if (!$email_message) {
		$email_message = "";
	}
	$email_message .= "\n\n<br><br>\n\n" . $sig;

	$subject = "Signature";

	$smtp = json_decode( file_get_contents( $root_path . "/config/smtp.json" ),false);
	if (empty($smtp)) {
		throw new Exception("SMTP Settings not set");
	}


	$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
	try {
		//Server settings
		$mail->SMTPDebug = 0;                                 // Enable verbose debug output
		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = $smtp->host;  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = $smtp->username;                 // SMTP username
		$mail->Password = $smtp->password;                           // SMTP password
		$mail->SMTPSecure = $smtp->security;                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $smtp->port;                                    // TCP port to connect to

		//Recipients
		$mail->setFrom($smtp->from_email, $smtp->from_name);
		$mail->addAddress($primary_email);     // Add a recipient



		//Content
		$mail->isHTML(true);                                  // Set email format to HTML
		$mail->Subject = $subject;
		$mail->Body    = $email_message;
		$mail->AltBody = $email_message;

		$mail->send();

	} catch (PHPMailer\PHPMailer\Exception $e) {
		throw new Exception( 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo);
	}




} catch (Exception $e) {
	JsonHelpers::printErrorJSONAndDie($e->getMessage() . "\n<br>\n" . $e->getTraceAsString());
}
JsonHelpers::printStatusJSONAndDie("Processed");