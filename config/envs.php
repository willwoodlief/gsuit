<?php
$top_root_path = realpath(dirname(__FILE__));
putenv("GOOGLE_APPLICATION_CREDENTIALS=$top_root_path/service-account.json");
putenv("GOOGLE_WEB_AUTH=$top_root_path/web-auth.json");


putenv("ROOT_PATH=$top_root_path");


//https://www.popmydesigns.com/manual_oauth_setup.php



