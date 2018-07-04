<?php
require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
# here, if something is called, we get the post vars and we update the user signature
# then we return json status of the operation, along with any message
//

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
	$gmail_client = create_email_client();
	$gmail = get_gmail_object($gmail_client,$primary_email,$send_as_email,$old_sig);
	set_email_signature($gmail,$sig,$primary_email,$alias);
} catch (Exception $e) {
	JsonHelpers::printErrorJSONAndDie($e->getMessage() . "\n<br>\n" . $e->getTraceAsString());
}
JsonHelpers::printStatusJSONAndDie("Processed");