<?php
$root_path = realpath(dirname(__FILE__));
putenv("GOOGLE_APPLICATION_CREDENTIALS=$root_path/service-account.json");
putenv("GOOGLE_WEB_AUTH=$root_path/web-auth.json");

//need to set up redirect. see if a file exists, if it does then its my localhost, else its on the test server
$my_localhost_check = (gethostname() === 'drat');
if ($my_localhost_check) {
	putenv("GOOGLE_WEB_AUTH_REDIRECT_URL=http://localhost/gsuit/manual_oauth_setup.php");
} else {
	putenv("GOOGLE_WEB_AUTH_REDIRECT_URL=https://www.popmydesigns.com/manual_oauth_setup.php");
}


