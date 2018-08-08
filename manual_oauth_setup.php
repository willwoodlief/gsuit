<?php
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
$issues = [];
$messages = [];
try {
	//Run this page to finish linking up the pages here with GSUIT
	if ( ! empty( $_REQUEST )  && isset($_REQUEST['code'])) {
		//store the token if it exists
		get_directory_client($_REQUEST['code'],$messages);
	}  else {
		//kick start the process
		get_directory_client(null,$messages,false);
	}
} catch (Exception $e) {
	$issues[] = $e->getMessage() . "\n" . $e->getTraceAsString();
}
//$code =  $_REQUEST['code'];
?>

<!DOCTYPE html>
<!--suppress JSCheckFunctionSignatures -->
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Finish Directory API Linkage</title>
    <style>

    </style>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <!--	fontawesome!-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
</head>
<body>
<?php print_alerts($messages,'success') ?>
<?php print_alerts($issues,'danger') ?>

<?php if(!empty($issues)) {?>
<row>
    <div class='col-sm-12 col-md-12 col-lg-12 '>
        <h2>There were errors here: </h2>
        <h4>You can contact <a href="mailto:willwoodlief+help@gmail.com"> The Developer </a> for assistance</h4>
        <?php if(!empty($_REQUEST)) { ?>
            <h4>This is a display of the request vars recieved</h4>
            <?php JsonHelpers::print_nice($_REQUEST);?>
        <?php } ?>
    </div>
</row>
<?php } ?>
<row>
    <div class='col-sm-12 col-md-12 col-lg-12 '>
        <h1>If there are no red alerts above, then this script is connected to the gsuit directory api </h1>
        <p> You can now leave this page and go to the <a href="scan.php"> Scan Page</a> </p>
    </div>

    <div class='col-sm-12 col-md-12 col-lg-12 '>

        <p> Or Go Back to the  <a href="settings.php">Settings Page</a> </p>
    </div>

</body>
</html>

