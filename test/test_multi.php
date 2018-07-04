<?php
require_once realpath(dirname(__FILE__)) . "/../vendor/autoload.php";
require_once realpath(dirname(__FILE__)) . "/../config/envs.php";
require_once realpath(dirname(__FILE__)) . "/../lib/helpers.php";
/**
 * @return Google_Client
 */
function create_email_client_test() {
	$client = new Google_Client();
	$client->useApplicationDefaultCredentials();
	$client->setScopes(array('https://www.googleapis.com/auth/gmail.settings.basic','https://www.googleapis.com/auth/gmail.settings.sharing'));
	return $client;
}

/**
 * @param Google_Client $client
 * @param string $user_to_impersonate
 * @param string $send_as_email
 * @param string $old_signature
 *
 * @return Google_Service_Gmail
 */
function get_gmail_object_test($client,$user_to_impersonate,&$send_as_email,&$old_signature) {
	$send_as_email = $old_signature = null;
	$client->setSubject($user_to_impersonate);

//Instantiates the Gmail services class with the client information
	$gmail = new Google_Service_Gmail($client);

	$res       = $gmail->users_settings_sendAs->listUsersSettingsSendAs($user_to_impersonate);
	$send_as = $res->getSendAs();
	foreach ($send_as as $sub_address) {
		if ($sub_address->isPrimary) {
			$send_as_email = $sub_address->sendAsEmail;
			$old_signature = $sub_address->signature;
		}
	}
	return $gmail;
}

/**
 * @param Google_Service_Gmail $gmail
 * @param $newSignature
 * @param $user_to_impersonate
 * @param $send_as_email
 */
function set_signature_test($gmail,$newSignature,$user_to_impersonate,$send_as_email) {
	$signature = new Google_Service_Gmail_SendAs();
	$signature->setSignature($newSignature);

//Send the request to update the signature
	$response = $gmail->users_settings_sendAs->patch($user_to_impersonate,$send_as_email,$signature);
	//print_r($response);
}






/**
 * @param Google_Client $client
 * @param array $user_hash (OUT REF)
 * @return array -- all the email addresses
 * @throws
 */
function get_user_info_test($client, array &$user_hash) {

	if (empty($user_hash) ) { $user_hash = [];}
	$email_addresses = [];
	$service = new Google_Service_Directory($client);

	$pageToken = NULL;
	$optParams = array(
		'customer' => 'my_customer'
	);

	try {
		do {
			if ($pageToken){
				$optParams['pageToken'] = $pageToken;
			}

			$results = $service->users->listUsers($optParams);
			$pageToken = $results->getNextPageToken();
			$users = $results->getUsers();

			foreach($users as $user) {
				$usersemails = $user->getPrimaryEmail();
				$email_addresses[] = $usersemails;
				$user_hash[$usersemails] = $user;
			}

		} while($pageToken);



	} catch (Exception $e) {
		print 'An error occurred: ' . $e->getMessage();
		throw $e;
	}

	return $email_addresses;
}
$messages = [];

$account_client = get_directory_client(null,$messages,true);;

//$user_to_impersonate = "william@popmydesigns.com";
$user_hash = [];
$emails = get_user_info_test($account_client,$user_hash);

//addresses [type],[formatted], organizations, organizations[title] [department],phones [type] [value], name (object) givenName, FamilyName, fullName
$client = create_email_client_test();

$td = $user_hash['anna@popmydesigns.com'];
$address =  $td->addresses[0]['formatted'];
$title =    $td->organizations[0]['title'];
$department =    $td->organizations[0]['department'];
$phones = [];

foreach ($td->phones as $phone)  {
	$phones[] = $phone['type'] . ' ' . $phone['value'];
}

$name = $td->name->fullName;
foreach ($emails as $email) {
	$gmail = get_gmail_object_test($client,$email,$send_as_email,$old_sig);
	print "Email: $email, send as: $send_as_email, old sig: $old_sig\n";
	set_signature_test($gmail,"<div><b>New Test Signature for $email</b></div>",$email,$send_as_email);
}
