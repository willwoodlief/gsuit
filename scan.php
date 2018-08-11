<?php
require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
$issues  = [];
$messages = [];
$debug_messages = [];
$user_hash = [];
$b_show_missing = false;
$paginator = null;
$change_divs = [];
global $not_in_users,$not_in_spreadsheet;
use JasonGrimes\Paginator;
try {

	$directory_client  = get_directory_client(null,$debug_messages,true);
	$gmail_client = create_email_client();
# first get the spreadsheet data
    $spreadsheet_data = get_most_recent_spreadsheet_data_from_db();
    $email_count_hash = get_email_count_hash();
    if (empty($spreadsheet_data)) {
        throw new Exception("There is no data in the most recent spreadsheet file uploaded");
    }
# then get the user data, its a hash keyed by the primary email address
    #note that this will always get all the users, no matter what kind of pagination is used, because there is a different order using the api and cannot do that
    # the pagination is simply to avoid having too many things on the browser page
    $user_data = get_user_info($directory_client,$user_hash);
	if (empty($user_hash)) {
		throw new Exception("There is no users in this GSuit, although the connections to it from this script work");
	}
# there are two modes of display here: if comparison is set,
    if (!empty($_REQUEST) && isset($_REQUEST['comparison'])) {
        #   1) then we build a list of email addresses which are in the spreadsheet but not the inboxes
        #   2) we build another list of inboxes that are not in the spreadsheet
        # This is handled by the script missing.php when is included here and we exit

        //build hash of spreadsheet users
        $spreadsheet_hash = make_spread_hash($spreadsheet_data);

	    //these are the emails in spreadsheet but not found in the users
        $not_in_users = array_diff_key ( $spreadsheet_hash,$user_hash  );

        //these are the emails in user but not found in the spreadsheet
        $not_in_spreadsheet = array_diff_key ( $user_hash , $spreadsheet_hash);
        $b_show_missing = true;
    }
    else {

	    # if comparison is not set
	    # then display is driven by what is in the spreadsheet
	    # is pagination set ? If not then its a 20 per page and starts on page 1
	    # figure out the pagination stuff
	    #
	    # include '../vendor/jasongrimes/paginator/examples/pagerSmall.phtml'; //optional templates like this

	    $b_show_missing = false;
	    $currentPage = 1;
	    if (!empty($_REQUEST) && isset($_REQUEST['page'])) {
		    $currentPage = intval($_REQUEST['page']);
	    }

	    $itemsPerPage = 6;
	    if (!empty($_REQUEST) && isset($_REQUEST['per_page'])) {
		    $itemsPerPage = intval($_REQUEST['per_page']);
	    }
	    $totalItems = sizeof($spreadsheet_data);
	    $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, "scan.php?page=(:num)&per_page=$itemsPerPage");


        # calculate starting offset in the spreadsheet

        $starting_row = ($currentPage-1) * $itemsPerPage;
        # then we are going to loop through and display, we start at offset and go to per page, in case there are too many for one screen
        # each loop, we get a row from the spreadsheet, using the email, we lookup the user data, if it does not exist we state that
        # if the user does exist, then we get their current signature, we calculate the new signature, and we make the div using that and the user info from google
        # we put each of these divs in an array $change_divs, and they will be output in html generation
        # if there is an error, then the errors are output before the divs

        $limit_of_this_page = $starting_row + $itemsPerPage - 1;
        if ($limit_of_this_page >= sizeof($spreadsheet_data)) {
	        $limit_of_this_page = sizeof($spreadsheet_data) -1 ;
        }
        for($i=$starting_row ; $i <= $limit_of_this_page ; $i++ ) {
            $data = $spreadsheet_data[$i];
            $footer = generate_footer($data);
	        //get the user information
	        $primary_email = $data['email'];
	        $send_as_email = $old_sig = null;
	        $b_valid = true;
            if (isset($user_hash[$primary_email])) {
                $user_info = generate_user_info($user_hash[$primary_email]);
	            $gmail = get_gmail_object($gmail_client,$primary_email,$send_as_email,$old_sig);
            } else {
	            $b_valid = false;
	            $user_info = 'This email is not a found email address for the GSuit';
            }

            $b_new = false;
            if ( isset($email_count_hash[$primary_email]) && $email_count_hash[$primary_email] <=1) {
	            $b_new = true;
            }

	        $entry = generate_entry_for_email($primary_email,$footer,$user_info,$old_sig,$send_as_email,$b_valid,$b_new);
	        $change_divs[] = $entry;


        }
    }
}
catch (Exception $e) {
	$issues[] = $e->getMessage() . "\n" . $e->getTraceAsString();
}

?>

<!DOCTYPE html>
<!--suppress JSCheckFunctionSignatures -->
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Scan Results for Email Footers</title>
	<style>
        table.edit-table {
            border-spacing: 0.5em;
            border-collapse: separate;
            padding: 1em;
            border: black solid 1px;
            width: 100%;
            margin-bottom: 2em;
        }


        table.edit-table th, table.edit-table td  {
            padding: 1em;
            text-align: left;
        }

        table.edit-table td.side {
            background-color: lightgrey;
        }

        span.edit-start {
            font-size: 20px;
            font-weight: bold;
        }

        span.edit-email {
            font-size: 18px;
            font-weight: bold;
            color: blue;
        }

        span.edit-header {
            font-size: 16px;
            font-weight: bold;
            color: black;
        }

        span.edit-note {
            font-size: 16px;
            font-weight: bold;
            color: green;
            font-style: italic;
        }

        span.user-data-header {
            font-weight: bold;
        }

        span.missing-email {
            font-size: 14px;
        }

        .new-email-address {
            background-color: rgba(203, 106, 139, 0.47);
        }

        table.edit-table td.alert-side {
            background-color: rgba(203, 106, 139, 0.47);
        }
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
    <script>

        $(function() {
            var public_ajax_obj = {nonce: null,action: 'update',ajax_url: 'update.php'};
            var ajax_obj = null;
            function talk_to_server(url_to, server_options, success_callback, error_callback) {

                if (!server_options) {
                    server_options = {};
                }

                // noinspection ES6ModulesDependencies
                var outvars = $.extend({}, server_options);
                // noinspection JSUnresolvedVariable
                outvars._ajax_nonce = public_ajax_obj.nonce;
                // noinspection JSUnresolvedVariable
                outvars.action = public_ajax_obj.action;

                // noinspection ES6ModulesDependencies
                // noinspection JSUnresolvedVariable
                ajax_obj = $.ajax({
                    type: 'POST',
                    beforeSend: function () {
                        if (ajax_obj && (ajax_obj !== 'ToCancelPrevReq') && (ajax_obj.readyState < 4)) {
                            //    ajax_obj.abort();
                        }
                    },
                    dataType: "json",
                    url: url_to,
                    data: outvars,
                    success: success_handler,
                    error: error_handler
                });

                function success_handler(data) {

                    // noinspection JSUnresolvedVariable
                    if (data.valid) {
                        if (data.hasOwnProperty('new_nonce') ) {
                            public_ajax_obj.nonce = data.new_nonce;
                        }
                        if (success_callback) {
                            success_callback(data);
                        } else {
                            console.debug(data);
                        }
                    } else {
                        if (error_callback) {
                            console.warn(data);
                            error_callback(null,data);
                        } else {
                            console.debug(data);
                        }

                    }
                }

                /**
                 *
                 * @param {XMLHttpRequest} jqXHR
                 * @param {Object} jqXHR.responseJSON
                 * @param {string} textStatus
                 * @param {string} errorThrown
                 */
                function error_handler(jqXHR, textStatus, errorThrown) {
                    if (errorThrown === 'abort' || errorThrown === 'undefined') return;
                    var what = '';
                    var message = '';
                    if (jqXHR && jqXHR.responseText) {
                        try {
                            what = $.parseJSON(jqXHR.responseText);
                            if (what !== null && typeof what === 'object') {
                                if (what.hasOwnProperty('message')) {
                                    message = what.message;
                                } else {
                                    message = jqXHR.responseText;
                                }
                            }
                        } catch (err) {
                            message = jqXHR.responseText;
                        }
                    } else {
                        message = "textStatus";
                        console.info('Admin Ecomhub ajax failed but did not return json information, check below for details', what);
                        console.error(jqXHR, textStatus, errorThrown);
                    }

                    if (error_callback) {
                        console.warn(message);
                        error_callback(message,null);
                    } else {
                        console.warn(message);
                    }


                }
            }

            $("button.update-sig").click(function() {
                var things = {sig: $(this).data('footer'),email:$(this).data('email'), alias: $(this).data('alias')};

                var that = this;
                var success = function(data) {
                    $(that).closest("td").find('span.status').text(data.message);
                };
                var error = function(msg) {
                    $(that).closest("td").find('span.error').text(msg);
                };
                talk_to_server("update.php",things, success,error)
            });

            $("button.email-sig").click(function() {
                var things = {sig: $(this).data('footer'),email:$(this).data('email'), alias: $(this).data('alias')};

                var that = this;
                var success = function(data) {
                    $(that).closest("td").find('span.status').html(data.message);
                };
                var error = function(msg) {
                    $(that).closest("td").find('span.error').html(msg);
                };
                talk_to_server("email_out.php",things, success,error)
            });

        });

    </script>
</head>
<body>
<?php print_alerts($messages,'success') ?>
<?php print_alerts($issues,'danger') ?>
<?php if ($b_show_missing) { ?>
    <?php include 'missing.php' ?>

    <div class="row" >
        <div class='col-sm-12 col-md-12 col-lg-12 '>
            <h3> To Change the email footers, <a href="scan.php" target="_blank">click here</a></h3>
        </div>
    </div>

<?php } else {?>
    <div class="row" style="margin: 5em">
        <div class='col-sm-12 col-md-12 col-lg-12 '>
            <h1> This page allows you to update the footers</h1>
            <h3> To View Discrepancies between the spreasheet and users in the gsuit, <a href="scan.php?comparison=1" target="_blank">click here</a></h3>
        </div>
    </div>
    <div class="row" style="margin: 5em">
        <?= $paginator ?>
    </div>
    <div class="row" style="margin: 5em">
		<?php foreach ($change_divs as $c) { ?>
			<?= $c ?>
		<?php } ?>
    </div>
    <div class="row" style="margin: 5em">
		<?= $paginator ?>
    </div>
<?php } ?>

</body>
</html>

