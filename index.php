<?php
require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/Input.php";
$issues         = [];
$messages       = [];
$debug_messages = [];

try {


	if ( isset( $_POST['submit-spreadsheet'] ) ) {
		$file = Input::get( 'spreadsheet', Input::MUST_BE_FILE );
		save_spreadsheet( $file['tmp_name'], $file['name'] );
	}

	$current_spreadsheet_info = get_current_spreadsheet_info();

} catch ( Exception $e ) {
	$issues[] = $e->getMessage() . "\n" . $e->getTraceAsString();
}

?>

<!DOCTYPE html>
<!--suppress JSCheckFunctionSignatures -->
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Update Email Footers</title>
    <style>
        span.wait-progress {
            display: none;
        }

        ul.unprocessed-emails li {
            color: red;
        }

        div.unprocessed-emails {
            display: none;
        }

        ul.processed-emails li {
            color: green;
        }

        div.processed-emails {
            display: none;
        }

        ul.ignored-emails li {
            color: black;
        }

        div.ignored-emails {
            display: none;
            margin-top: 2em;
        }


    </style>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"
          integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
            integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
            crossorigin="anonymous"></script>

    <!--	fontawesome!-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css"
          integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">

    <script src="timestamp_to_locale.js"></script>
    <script>

        $(function () {
            var public_ajax_obj = {nonce: null, action: 'update', ajax_url: 'update_all.php'};
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

            $("button.update-all").click(function () {

                var status_span = $(this).closest("div.update-all-holder").find('span.status');
                var error_span = $(this).closest("div.update-all-holder").find('span.error');
                var spinner = $(this).closest("div.update-all-holder").find('span.wait-progress');


                $('ul.unprocessed-emails').html('');
                $('div.unprocessed-emails').hide();
                $('ul.processed-emails').html('');
                $('div.processed-emails').hide();

                $('ul.ignored-emails').html('');
                $('div.ignored-emails').hide();


                status_span.text('');
                error_span.text('');
                var things = {update_all: true};


                spinner.show();
                var success = function (data) {
                    spinner.hide();
                    status_span.text(data.message.words);

                    if (data.message.not_included.length > 0) {

                        $('div.unprocessed-emails').show();
                        for (var i = 0; i < data.message.not_included.length; i++) {
                            $('ul.unprocessed-emails').append("<li>" + data.message.not_included[i] + "</li>");
                        }
                    }

                    if (data.message.actions.length > 0) {

                        $('div.processed-emails').show();
                        for (var j = 0; j < data.message.actions.length; j++) {
                            $('ul.processed-emails').append("<li>" + data.message.actions[j].email + "</li>");
                        }
                    }

                    if (data.message.not_processed.length > 0) {

                        $('div.ignored-emails').show();
                        for (var k = 0; j < data.message.not_processed.length; k++) {
                            $('ul.ignored-emails').append("<li>" + data.message.not_processed[k] + "</li>");
                        }
                    }


                };
                var error = function (msg) {
                    error_span.text(msg);
                    spinner.hide();
                };
                talk_to_server("update_all.php", things, success, error)
            });

            $("button.email-all-credentials").click(function () {


                var status_span = $(this).closest("div.email-all-holder").find('span.status');
                var error_span = $(this).closest("div.email-all-holder").find('span.error');
                var spinner = $(this).closest("div.email-all-holder").find('span.wait-progress');

                var things = {email_all: true};
                error_span.text('');
                status_span.text('');

                spinner.show();
                var success = function (data) {
                    spinner.hide();
                    status_span.html(data.message);

                };
                var error = function (msg) {
                    error_span.html(msg);
                    spinner.hide();
                };
                talk_to_server("email_out.php", things, success, error)
            });


        });

    </script>
</head>
<body>
<?php print_alerts( $messages, 'success' ) ?>
<?php print_alerts( $issues, 'danger' ) ?>
<div class="container">
    <div class="row" style="margin-bottom: 2em">
        <div class="col-sm-12">
            <h1 class="display-2">Upload a Spreadsheet And/Or Start to Change Footers</h1>
        </div>
    </div>

    <div class="row" style="">

        <div class="col-sm-4 col-sm-offset-4 ">
            <div class="form-group">
                <form action="index.php" method="post" enctype="multipart/form-data">
                    <div style="margin-bottom: 2em">
						<?php if ( $current_spreadsheet_info ) { ?>
                            Spreadsheet <b><?= $current_spreadsheet_info['uploaded_file_name'] ?></b> was uploaded on
                            <br>
                            <span class="a-timestamp-full-date-time"
                                  data-ts="<?= $current_spreadsheet_info['create_ts'] ?>"></span>
                            <br>
                            And has <b><?= sizeof( $current_spreadsheet_info['data'] ) ?></b> Entries
						<?php } else { ?>
                            No Spreadsheet was Uploaded
						<?php } ?>
                    </div>
                    <span class="btn btn-default btn-block btn-file">
                        <input type="file" name="spreadsheet" id="input-spreadsheet">
                    </span>
                    <br>
                    <button type="submit" name="submit-spreadsheet"
                            class="btn btn-success btn-block full-width form-control" style="margin-top: 1em">Upload A
                        Spreadsheet
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top: 2em">
        <div class="col-sm-4">
            <p>
                You can use this button to review the contents of the spreadsheet, and optionally, also update things
                one at a time
            </p>
            <button class="btn btn-large btn-block btn-primary full-width" type="button"
                    onclick="window.location='scan.php'">Review And Submit Individually
            </button>
        </div>
        <div class="col-sm-4">

            <p>
                This will download all the signatures in one file. Only accounts that already exist in the gsuit domain will be included in this download
            </p>
            <form action="download.php" method="post" class="download-single-sig-form" enctype="multipart/form-data">
                <input type="hidden" name="style" value="all">
                <button type="submit" name="download-all-credentials"
                        class="btn btn-default btn-block full-width form-control" style="" value="1">
                    Download All Signatures As HTML
                </button>
            </form>

            <br>
            <br>

            <div class="email-all-holder">
                <p>
                    This will email all signatures out. Only accounts that already exist in the gsuit domain will be emailed
                </p>

                    <input type="hidden" name="style" value="all">
                    <button type="button" name="email-all-credentials"
                            class="btn btn-default btn-block full-width form-control email-all-credentials" style="" value="1">
                        Email All Signatures
                    </button>

                <span class="wait-progress">
                <i class="fas fa-spin fa-spinner " style="font-size: 3em"></i>
                <span style="font-size: 2em;margin-left: 1em"> In Progress  </span>
            </span>
                <br>
                <span class="status" style="font-size: larger;color:green"></span>
                <span class="error" style="font-size: larger;color:red"></span>
            </div>

        </div>
        <div class="col-sm-4 update-all-holder">
            <p>
                If you press this button, then everything that is in the spreadsheet (browsable using the Review and
                Submit Button)
                Will be updated at once
            </p>
            <button class="btn btn-large btn-block btn-primary full-width update-all" type="button">Update Everything in
                the
                Spreadsheet
            </button>
            <br>
            <span class="wait-progress">
                <i class="fas fa-spin fa-spinner " style="font-size: 3em"></i>
                <span style="font-size: 2em;margin-left: 1em"> In Progress  </span>
            </span>
            <br>
            <span class="status" style="font-size: larger;color:green"></span>
            <span class="error" style="font-size: larger;color:red"></span>
            <div class="unprocessed-emails">
                <span style="color: indigo">Some Email Were Not Processed. They were in the spreadsheet but not in GSuit</span>
                <ul class="unprocessed-emails"></ul>
            </div>

            <br>
            <div class="processed-emails">
                <span style="color: green">Emails That Were Processed</span>
                <ul class="processed-emails"></ul>
            </div>

            <div class="ignored-emails">
                <span style="color: black">Emails That Were Ignored (not in spreadsheet)</span>
                <ul class="ignored-emails"></ul>
            </div>

        </div>
    </div>
</div>
</body>
</html>

