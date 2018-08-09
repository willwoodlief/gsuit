<?php
require_once realpath( dirname( __FILE__ ) ) . "/../vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/../config/envs.php";
require_once realpath( dirname( __FILE__ ) ) . "/JsonHelpers.php";
require_once realpath( dirname( __FILE__ ) ) . "/sql_db_class.php";


function get_email_count_hash() {
	//
	global $db;
	$res = $db->fetch_array( "select email, count(email) as da_count from accounts group by email" );
	if (!$res) { return [];}
	$ret = [];
	foreach ($res as $row) {
		$ret[$row['email']] = $row['da_count'];
	}
	return $ret;
}

/**
 * @return array|false
 * @throws Exception
 */
function get_current_spreadsheet_info() {
	global $db;
	$recent_id_array = $db->fetch_array( "select id from sheets order by id desc limit 1" );
	if ( sizeof( $recent_id_array ) === 0 ) {
		throw new Exception( "No sheets entered in db" );
	}
	if (sizeof($recent_id_array) == 0) {
		return false;
	}
	$sheet_id = $recent_id_array[0]['id'];

	$the_sheet_info_array = $db->fetch_array("select uploaded_file_name,uploaded_date,
													CAST(strftime('%s', uploaded_date) AS INT) as create_ts
													 from sheets where id = $sheet_id");
	if (sizeof($the_sheet_info_array) == 0) {
		return false;
	}
	$the_sheet_info = $the_sheet_info_array[0];
	$the_data = get_most_recent_spreadsheet_data_from_db($sheet_id);
	$the_sheet_info['data'] = $the_data;
	return $the_sheet_info;
}
/**
 * @param integer $sheet_id
 * @throws Exception
 * @return array
 */
function get_most_recent_spreadsheet_data_from_db($sheet_id = null) {
	global $db;
	if (!$sheet_id) {
		//get the last sheet id entered
		$recent_id_array = $db->fetch_array( "select id from sheets order by id desc limit 1" );
		if ( sizeof( $recent_id_array ) === 0 ) {
			throw new Exception( "No sheets entered in db" );
		}
		$sheet_id = $recent_id_array[0]['id'];
	}
	$datas  = $db->fetch_array("select sheet_id,email,first_name,last_name,company,function,mobile_phone,
										office_phone,address_line_1,address_line_2,city,postal_code,
										website,twitter,facebook,linkedin from accounts where sheet_id = $sheet_id");
	return $datas;
}
/**
 * Saves to the database
 * @param string $inputFileName
 * @param string $original_name
 * @throws PHPExcel_Exception
 */
function save_spreadsheet($inputFileName,$original_name) {
	global $db;
	//get the data and validate the file
	$data = get_spreadsheet_data($inputFileName);

	//create the new sheet in the database, and get the last_id
	$db->insert('sheets',["uploaded_file_name"=>$original_name]);
	$what = $db->fetch_array("SELECT last_insert_rowid() as last_id");
	$last_id = $what[0]['last_id'];

	foreach ($data as $row) {

		if (empty(trim($row['email']) )) {continue;}
		$insert = [
			'sheet_id' => $last_id,
			'email'=> $row['email'],
			'first_name'=> $row['first name'],
			'last_name'=> $row['last name'],
			'company'=> $row['company name'],
			'function'=> $row['function'],
			'mobile_phone'=> $row['mobile'],
			'office_phone'=> $row['tel office'],
			'address_line_1'=> $row['address line 1'],
			'address_line_2'=> $row['address line2'],
			'city'=> $row['city'],
			'postal_code'=> $row['postal code'],
			'website'=> $row['website'],
			'twitter'=> $row['twitter'],
			'facebook'=> $row['facebook'],
			'linkedin'=> $row['linkedin']
		];

		$db->insert('accounts',$insert);
	}
}
/**
 * @param string $inputFileName
 *
 * @throws PHPExcel_Exception
 * @return array
 */
function get_spreadsheet_data($inputFileName) {

	if (!file_exists($inputFileName)) {
		throw new PHPExcel_Exception("The file $inputFileName does not exit");
	}

//  Read your Excel workbook
	try {
		$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		$objPHPExcel = $objReader->load($inputFileName);
	} catch(PHPExcel_Exception $e) {
		throw $e;
	}

//  Get worksheet dimensions
	$sheet = $objPHPExcel->getSheet(0);
	$highestRow = $sheet->getHighestRow();
	$highestColumn = "Z";

	$template = [];
	$data = [];
//  Loop through each row of the worksheet in turn
	for ($row = 1; $row <= $highestRow; $row++){
		//  Read a row of data into an array
		$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
			NULL,
			TRUE,
			FALSE);
		//  Insert row data array into your database of choice here
		//print_r($rowData);
		if ($row === 1) {
			$column_names = $rowData[0];
			for($j = 0;$j < sizeof($column_names); $j++) {
				if (!empty($column_names[$j])) {
					$template[$j] = strtolower(trim($column_names[$j]) );
				}
			}
		} else {
			$node = [];
			foreach ($template as $key => $value) {
				$node[$value] = null;
			}

			$row_of_data = $rowData[0];
			foreach ($row_of_data as $da_index => $da_value) {
				if (!empty(trim($da_value))) {
					$lookup = $template[$da_index];
					$node[$lookup] = trim($da_value);
				}
			}
			array_push($data,$node);
		}
	}
	return $data;
}


function make_spread_hash($spreadsheet_data) {
	$ret = [];
	foreach ($spreadsheet_data as $data) {
		$email = $data['email'];
		$ret[$email] = $data;
	}
	return $ret;
}


function normalizePath($path) {
	/** @noinspection PhpDeprecationInspection */
	return array_reduce(explode('/', $path), create_function('$a, $b', '
			if($a === 0)
				$a = "/";

			if($b === "" || $b === ".")
				return $a;

			if($b === "..")
				return dirname($a);

			return preg_replace("/\/+/", "/", "$a/$b");
		'), 0);
}

/**
 * if code is empty will read it from the stored token, else will redirect to the auth
 * @param null|string $code
 * @param array $messages
 * @param boolean $b_throw_if_cant
 * @return Google_Client
 * @throws Google_Exception
 * @throws Exception
 */
function get_directory_client($code ,array &$messages, $b_throw_if_cant = true)
{
	$redirect_url = get_url('GOOGLE_WEB_AUTH_REDIRECT_URL');
	if (empty($messages)) {
		$messages = [];
	}
	$client = new Google_Client();
	$client->setApplicationName('G Suite Directory API PHP Quickstart');
	/** @noinspection PhpParamsInspection */
	$client->setScopes(Google_Service_Directory::ADMIN_DIRECTORY_USER_READONLY);
	$client->setAuthConfig(getenv('GOOGLE_WEB_AUTH'));
	$client->setRedirectUri($redirect_url);
	$client->setAccessType('offline');
	$client->setApprovalPrompt('force');

	// Load previously authorized credentials from a file.
	$credentialsPath = realpath(dirname(__FILE__) . "/../config/credentials.json");
	if (!$credentialsPath) {
		$credentialsPath = normalizePath(realpath(dirname(__FILE__) ) . "/../config/credentials.json");
	}
	if (file_exists($credentialsPath)) {
		$accessToken = json_decode(file_get_contents($credentialsPath), true);
		$messages[] = sprintf("Credentials previously saved to %s\n", $credentialsPath);
	} else {

		if (!empty($code) ) {
			$authCode = trim($code);
			// Exchange authorization code for an access token.
			$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
			// Store the credentials to disk.
			file_put_contents($credentialsPath, json_encode($accessToken));
			chmod($credentialsPath, 0666);
			$messages[] = sprintf("Credentials saved to %s\n", $credentialsPath);
		} else {
			if ($b_throw_if_cant) {
				throw new Exception("Need to run manual_oauth_setup.php before running this page");
			}
			//redirect to auth url
			// Request authorization from the user.
			$authUrl = $client->createAuthUrl();
			header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
			exit;
		}

	}
	$client->setAccessToken($accessToken);

	// Refresh the token if it's expired.
	if ($client->isAccessTokenExpired()) {
		$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
		file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
		$messages[] = sprintf("Credentials previously saved to %s have been refreshed\n", $credentialsPath);
	}
	return $client;
}

/**
 * @return Google_Client
 */
function create_email_client() {
	$client = new Google_Client();
	$client->useApplicationDefaultCredentials();
	$client->setScopes(array('https://www.googleapis.com/auth/gmail.settings.basic','https://www.googleapis.com/auth/gmail.settings.sharing'));
	return $client;
}

/**
 * @param Google_Client $client
 * @param array $user_hash (OUT REF)
 * @return array -- all the email addresses
 * @throws
 */
function get_user_info($client, array &$user_hash) {

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


/**
 * @param Google_Client $client
 * @param string $user_to_impersonate
 * @param string $send_as_email
 * @param string $old_signature
 *
 * @return Google_Service_Gmail
 */
function get_gmail_object($client,$user_to_impersonate,&$send_as_email,&$old_signature) {
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
 * Send Message.
 *
 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
 * @param  string $toAddress, email address this is going to
 * @param  string|null $strSubject message subject line
 * @param  string  $message Message to send.
 * @return Google_Service_Gmail_Message sent Message.
 */
function sendMessage($service, $toAddress, $strSubject,  $message) {

	if (empty($strSubject)) {
		$strSubject =  'Test mail using GMail API' . date('M d, Y h:i:s A');
	}


	$strRawMessage = '';
	//$strRawMessage .= "From: myAddress<myemail@gmail.com>\r\n";
	$strRawMessage .= "To: $toAddress\r\n";
	$strRawMessage .= 'Subject: =?utf-8?B?' . base64_encode($strSubject) . "?=\r\n";
	$strRawMessage .= "MIME-Version: 1.0\r\n";
	$strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
	$strRawMessage .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
	if (empty($message)) {
		$strRawMessage .= "this <b>is a test message!\r\n";
	} else {
		$strRawMessage .= $message;
	}

	// The message needs to be encoded in Base64URL
	$mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
	$msg = new Google_Service_Gmail_Message();
	$msg->setRaw($mime);
	//The special value **me** can be used to indicate the authenticated user.
	$message = $service->users_messages->send($toAddress, $msg);


	return $message;

}
/**
 * Prints out html for errors
 *
 * Allows messages to be put on screen in a nice way
 * Requires bootstrap js and css to in the page
 * Also, assumes inside a bootstrap container or container-fluid  div
 * @param array $alerts <p>
 *  array of things that will be converted to strings
 * </p>
 * @param string $style <p>
 *   one of danger,warning,info, success
 * </p>
 *
 * @return void
 */
function print_alerts(array $alerts,$style='danger') {


	switch ($style) {
		case 'danger':
			{
				$title = "Error!";
				break;
			}
		case 'warning':
			{
				$title = "Warning";
				break;
			}
		case 'info':
			{
				$title = "Notice";
				break;
			}

		case 'success':
			{
				$title = "Success";
				break;
			}

		default: {
			$style = 'info';
			$title = "Message";
		}
	}

	print "<!-- Generated by print_alerts -->\n";
	print "<div class='row'>\n";
	print "  <div class='col-sm-12 col-md-8 col-md-offset-2 '>\n";
	foreach ($alerts as $alert) {
		$alert = strval($alert);
		print "    <div class='alert alert-$style alert-dismissible' role='alert'>\n";
		print '      <a href="#" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>'."\n";

		print "      <strong>$title</strong><pre> $alert"."</pre>\n";
		print "    </div>\n";

	}
	print "  </div>\n";
	print "</div>\n";
}

/**
 * @param $data
 *
 * @return string
 * @throws Exception
 */
function generate_footer($data) {

	global $spread_facebook,$spread_linkedin,$spread_twitter,$spread_address_line2;

	$spread_twitter = $data['twitter'];
	$spread_linkedin = $data['linkedin'];
	$spread_facebook = $data['facebook'];
	$spread_address_line2 = $data['address_line_2'];
	$tags = [
		'[spead_email]' => $data['email'],
		'[spread_name]' => $data['first_name'] . ' ' . $data['last_name'],
		'[spread_company]' => $data['company'],
		'[spread_function]' => $data['function'],
		'[spread_mobile]' => $data['mobile_phone'],
		'[spread_phone]' => $data['office_phone'],
		'[spread_address_line1]' => $data['address_line_1'],
		'[spread_address_line2]' => $data['address_line_2'],
		'[spread_address_city]' => $data['city'],
		'[spread_address_postal]' => $data['postal_code'],
		'[spread_website]' => $data['website'],
		'[spread_twitter]' => $spread_twitter,
		'[spread_linkedin]' => $spread_linkedin,
		'[spread_facebook]' => $spread_facebook,
		'[banner_url]' => get_image_url('banner'),
		'[logo_url]' => get_image_url('logo'),
		'[twitter_url]' => get_image_url('twitter'),
		'[linked_in_url]' => get_image_url('linkedin'),
		'[facebook_url]' => get_image_url('facebook')
	];


	ob_start();
	include realpath( dirname( __FILE__ ) ) . "/../templates/signature_template.php";
	$footer = ob_get_contents();
	ob_end_clean();
	$processed_footer = str_replace(array_keys($tags), $tags, $footer);
	return $processed_footer;
}

function generate_user_info($td) {
	global $email,$address,$title, $department,$phones,$name;
	$email = $td->primaryEmail;
	$address =  $td->addresses[0]['formatted'];
	$title =    $td->organizations[0]['title'];
	$department =    $td->organizations[0]['department'];
	$phones = [];

	if (!empty($td->phones)) {
		foreach ($td->phones as $phone)  {
			$phones[] = $phone['type'] . ' ' . $phone['value'];
		}
	}


	$name = $td->name->fullName;

	ob_start();
	include realpath( dirname( __FILE__ ) ) . "/../templates/user_info.php";
	$info = ob_get_contents();
	ob_end_clean();
	return $info;
	//return ['name'=>$name, 'address'=>$address,'title'=>$title,'department' => $department, 'phones' =>$phones];
}

function generate_entry_for_email(
	/** @noinspection PhpUnusedParameterInspection */
	$primary_email,$footer,$user_info,$old_sig,$send_as_email,$b_valid,$b_new) {
	//if footer = $old_sig then this will not be updated
	ob_start();
	include realpath( dirname( __FILE__ ) ) . "/../templates/entry_div.php";
	$info = ob_get_contents();
	ob_end_clean();
	return $info;
}

function set_email_signature($gmail,$newSignature,$user_to_impersonate,$send_as_email) {
	$signature = new Google_Service_Gmail_SendAs();
	$signature->setSignature($newSignature);

//Send the request to update the signature
	$gmail->users_settings_sendAs->patch($user_to_impersonate,$send_as_email,$signature);
}

/**
 * @param string $type
 * @return string|null
 * @throws Exception
 */
function get_image_url($type) {
	$base_path = realpath(dirname(__FILE__)."/../res/$type");
	if (!$base_path) {
		throw new Exception("$type is not a valid image category");
	}
	$files = [];
	$all_files = scandir($base_path);
	foreach ($all_files as $f) {
		if ($f === '.' || $f === '..' || $f === '.keep') {continue;}
		$files[] = $f;
	}

	if (sizeof($files) === 0) {
		return null;
	}

	if (sizeof($files)> 1) {
		throw new Exception("Too many files in  $type. Should only be one image");
	}

	$web_root = get_url('WEB_ROOT');
	$file = $files[0];
	$url =  "$web_root/res/$type/$file";
	return $url;



}


/**
 * @param string $type
 * @return string|null
 * @throws Exception
 */
function get_image_path($type) {
	$base_path = realpath(dirname(__FILE__)."/../res/$type");
	if (!$base_path) {
		throw new Exception("$type is not a valid image category");
	}
	$files = [];
	$all_files = scandir($base_path);
	foreach ($all_files as $f) {
		if ($f === '.' || $f === '..' || $f === '.keep') {continue;}
		$files[] = $f;
	}

	if (sizeof($files) === 0) {
		return null;
	}

	if (sizeof($files)> 1) {
		throw new Exception("Too many files in  $type. Should only be one image");
	}
	return $base_path . '/' .$files[0];


}


/**
 * @param $type
 * @param $file_name
 * @param $file_path
 *
 * @throws Exception
 */
function set_image_from_file($type,$file_name,$file_path) {
	$base_path = realpath(dirname(__FILE__)."/../res/$type");
	if (!$base_path) {
		throw new Exception("$type is not a valid image category");
	}
	//get the old image
	$old_image_path = get_image_path($type);
	if ($old_image_path) {
		if (!unlink($old_image_path)) {
			throw new Exception("Could not delete $old_image_path, check permissions");
		}
	}

	$new_path = $base_path . '/' .  $file_name;
	if (!copy($file_path,$new_path) ) {
		throw new Exception("could not copy $file_path to $new_path ");
	}

}

/**
 * @param $var
 * @param $value
 * @return void
 * @throws Exception
 */
function replace_env_var($var,$value) {
	//get path for the env file
	$base_path = realpath(dirname(__FILE__)."/../config/envs.php");
	if (!$base_path) {
		throw new Exception("cannot find config/envs.php");
	}
	//suck in file
	$env_file = file_get_contents($base_path);
	if (!$env_file) {
		throw new Exception("Cannot Read the config/envs.php");
	}

	//create regular expression to search and replace

	//'#(?P<env_pre>putenv\("$var=)(?P<env_val>[^"]+)(?P<env_end>"\))#Di'

	$pattern = '#(?P<env_pre>putenv\("'.$var.'=)(?P<env_val>[^"]+)(?P<env_end>"\))#Di';
	$newandimproved =preg_replace_callback($pattern, function ($match) use($value){
		return $match[1].$value.$match[3];
	}, $env_file);

	file_put_contents($base_path,$newandimproved);

	//write file back out
}

/**
 * @param string $which
 *
 * @return string
 * @throws Exception
 */
function get_url($which) {
	$base_path = realpath(dirname(__FILE__)."/../config/urls.json");
	if (!$base_path) {
		throw new Exception("cannot find config/config/urls.json");
	}
	//suck in file
	$env_file = file_get_contents($base_path);
	if (!$env_file) {
		throw new Exception("Cannot Read the config/envs.php");
	}
	$urls = json_decode($env_file,true);
	if (!array_key_exists($which,$urls)) {
		throw new Exception("$which is not found in the url json hash at config/urls.json");
	}
	return $urls[$which];
}


/**
 * @param string $which
 * @param string $new_url
 * @return void
 * @throws Exception
 */
function save_url($which,$new_url) {
	$base_path = realpath(dirname(__FILE__)."/../config/urls.json");
	if (!$base_path) {
		throw new Exception("cannot find config/config/urls.json");
	}
	//suck in file
	$env_file = file_get_contents($base_path);
	if (!$env_file) {
		throw new Exception("Cannot Read the config/envs.php");
	}
	$urls = json_decode($env_file,true);
	if (!array_key_exists($which,$urls)) {
		throw new Exception("$which is not found in the url json hash at config/urls.json");
	}
	$urls[$which] = $new_url;
	$new_envs = json_encode($urls);
	file_put_contents($base_path,$new_envs);
}


