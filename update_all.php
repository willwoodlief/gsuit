<?php
set_time_limit( 0 );
require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
# here, if something is called, we get the post vars and we update the user signature
# then we return json status of the operation, along with any message
//
$not_included = [];
$debug_messages = [];
$not_in_spreadsheet = [];
try {
	if (!isset($_POST['update_all'])) {
		throw new Exception("update_all needs to be set in the post");
	}
	$directory_client  = get_directory_client(null,$debug_messages,true);
	$user_hash = [];
	$user_data = get_user_info($directory_client,$user_hash);
	$current_spreadsheet = get_current_spreadsheet_info();
	$data = $current_spreadsheet['data'];
	$gmail_client = create_email_client();
	$action_hash = [];
	$send_as_email = $old_sig = null;
	foreach ($data as $row) {
		$primary_email = $row['email'];
		if (!isset($user_hash[$primary_email])) {
			$not_included[] = $primary_email;
			continue; //some things in the spreadsheet may not be on the gsuit
		}
		//get alias
		$gmail = get_gmail_object($gmail_client,$primary_email,$send_as_email,$old_sig);
		$sig =  generate_footer($row);
		set_email_signature($gmail,$sig,$primary_email,$send_as_email);
		$action_hash[] = ['alias'=>$send_as_email,'sig'=>$sig,'email'=>$primary_email];
	}

	//build hash of spreadsheet users
	$spreadsheet_hash = make_spread_hash($data);

	//get all the GSuit accounts that were not updated in this action
	$not_in_spreadsheet_hash = array_diff_key ( $user_hash , $spreadsheet_hash);

	foreach ($not_in_spreadsheet_hash as $not => $here) {
		$not_in_spreadsheet[] = $not;
	}


} catch (Exception $e) {
	JsonHelpers::printErrorJSONAndDie($e->getMessage() . "\n<br>\n" . $e->getTraceAsString());
}
$count = sizeof($action_hash);
JsonHelpers::printStatusJSONAndDie(['words'=>"processed $count emails from spreadsheet","not_included"=>$not_included,
                                    "actions"=>$action_hash,"not_processed" => $not_in_spreadsheet] );