<?php
set_time_limit( 0 );
require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
# here, if something is called, we get the post vars and we update the user signature
# then we return json status of the operation, along with any message
//
use PHPMailer\PHPMailer\PHPMailer;


function stest( $ip, $portt ) {
	$fp = @fsockopen( $ip, $portt, $errno, $errstr, 0.1 );
	if ( ! $fp ) {
		return false;
	} else {
		fclose( $fp );

		return true;
	}
}

/**
 * @param stdClass $smtp
 * @param $primary_email
 * @param $sig
 *
 * @throws \Exception
 */
function email_out($smtp,$primary_email,$sig) {
	$root_path     = realpath( dirname( __FILE__ ) );
	$email_message = file_get_contents( $root_path . "/templates/email_notice_template.php" );
	if ( ! $email_message ) {
		$email_message = "";
	}
	$email_message .= "\n\n<br><br>\n\n" . $sig;

	$subject = "Signature";




	$mail = new PHPMailer( true );                              // Passing `true` enables exceptions


	if ( ! stest( $smtp->host, $smtp->port ) ) {
		throw new \Exception( "Cannot reach " . $smtp->host . ' ' . $smtp->port );
	} else {
		Print "[Port is Open]\n";
	}
	//Server settings
	$mail->SMTPDebug = 2;                                 // Enable verbose debug output
	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host       = $smtp->host;  // Specify main and backup SMTP servers
	$mail->SMTPAuth   = true;                               // Enable SMTP authentication
	$mail->Username   = $smtp->username;                 // SMTP username
	$mail->Password   = $smtp->password ;                           // SMTP password
	$mail->SMTPSecure = $smtp->security;                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port       = $smtp->port;                                    // TCP port to connect to

	//Recipients
	$mail->setFrom( $smtp->from_email );
	$mail->addAddress( $primary_email );     // Add a recipient


	//Content
	$mail->isHTML( true );                                  // Set email format to HTML
	$mail->Subject = $subject;
	$mail->Body    = $email_message;
	$mail->AltBody = $email_message;

	$mail->send();
}

/**
 * @throws Google_Exception
 * @throws Exception
 */
function get_sig_hash(){
	$debug_messages = [];
	$directory_client    = get_directory_client( null, $debug_messages, true );
	$user_hash           = [];
	get_user_info( $directory_client, $user_hash );
	$current_spreadsheet = get_current_spreadsheet_info();
	$data                = $current_spreadsheet['data'];
	$action_hash         = [];

	foreach ( $data as $row ) {
		$primary_email = $row['email'];
		if ( ! isset( $user_hash[ $primary_email ] ) ) {
			$not_included[] = $primary_email;
			continue; //some things in the spreadsheet may not be on the gsuit
		}

		$sig   = generate_footer( $row );
		$action_hash[$primary_email] = $sig;
	}
	return $action_hash;
}

try {
	ob_start();
	$email_count = 1;
	$root_path     = realpath( dirname( __FILE__ ) );
	$smtp = json_decode( file_get_contents( $root_path . "/config/smtp.json" ), false );
	if ( empty( $smtp ) ) {
		throw new Exception( "SMTP Settings not set" );
	}
	if ( isset( $_POST['email_all'] ) ) {
		$sig_hash = get_sig_hash();
		foreach ($sig_hash as $primary_email => $sig) {
			email_out($smtp,$primary_email,$sig);
		}
		$email_count = sizeof($sig_hash);

	} else {
		if ( ! isset( $_POST['email'] ) ) {
			throw new Exception( "Email needs to be in the post" );
		}

		if ( ! isset( $_POST['sig'] ) ) {
			throw new Exception( "New Signature needs to be in the post" );
		}

		if ( ! isset( $_POST['alias'] ) ) {
			throw new Exception( "Alias needs to be in the post" );
		}


		$primary_email = $_POST['email'];
		$alias         = $_POST['alias'];
		$sig_base_64   = $_POST['sig'];
		$sig           = base64_decode( $sig_base_64 );
		if ( ! $sig ) {
			throw new Exception( "could not unencode base64 signature" );
		}

		email_out($smtp,$primary_email,$sig);


	}


} catch ( Exception $e ) {
	$html = ob_get_contents();
	ob_end_clean();
	if ( empty( trim( $html ) ) ) {
		$html = "Debug was empty!\n";
	}
	JsonHelpers::printErrorJSONAndDie( '' . $e->getMessage() . "<pre>\n Debug is: " . $html . "\n\n" . $e->getTraceAsString() ."</pre>" );
}
$html = ob_get_contents();
ob_end_clean();
//JsonHelpers::printStatusJSONAndDie( "Processed<pre>\n" . $html ."</pre>");
JsonHelpers::printStatusJSONAndDie( "Email Processed ($email_count)");