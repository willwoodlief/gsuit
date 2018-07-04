<?php
require_once realpath(dirname(__FILE__)) . "/../vendor/autoload.php";
require_once realpath(dirname(__FILE__)) . "/../config/envs.php";

//This is the primary email of the person whos' signature you want to update
$user_to_impersonate = "william@popmydesigns.com";
$send_as_email = null;
$old_signature = null;

//Instantiates the Google client class with the scopes and variables to update the signature
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->setScopes(array('https://www.googleapis.com/auth/gmail.settings.basic','https://www.googleapis.com/auth/gmail.settings.sharing'));
$client->setSubject($user_to_impersonate);

//Instantiates the Gmail services class with the client information
$gmail = new Google_Service_Gmail($client);

$res       = $gmail->users_settings_sendAs->listUsersSettingsSendAs($user_to_impersonate);
print_r($res);
$send_as = $res->getSendAs();
print "\n---------------\n";
foreach ($send_as as $sub_address) {
	print_r($sub_address);
	if ($sub_address->isPrimary) {
		$send_as_email = $sub_address->sendAsEmail;
		$old_signature = $sub_address->signature;
	}
}
print "\n-----------\n";
print "send as  = $send_as_email\n";
print "old sig  = $old_signature\n";



//Set the HTML for your new signature
$newSignature = "New Email Signature for $send_as_email set by script! <b>Hi There Will!,</b>";

// Instantiate the Gmail SendAs class and set the signature value
$signature = new Google_Service_Gmail_SendAs();
$signature->setSignature($newSignature);

//Send the request to update the signature
$response = $gmail->users_settings_sendAs->patch($user_to_impersonate,$send_as_email,$signature);
print "\n-----------\n";
print "response for setting\n";
print_r($response);