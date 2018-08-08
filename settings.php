<?php
require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
require_once realpath( dirname( __FILE__ ) ) . "/lib/Input.php";

class UserPreException extends Exception {
}

$issues         = [];
$messages       = [];
$debug_messages = [];

try {
    $root_path  = realpath(dirname(__FILE__));
	//make sure config is writable for new files
	$file           = $root_path . '/config';
	$need_adjusting = [];
	if ( ! is_writable( $file ) ) {
		if ( file_exists( $file ) ) {
			$need_adjusting[] = $file;
		}
	}




	$relative_files_to_check = [
		'config/sheets.db',
		'config/credentials.json',
		'config/service-account.json',
		'config/web-auth.json',
		'config/envs.php',
		'res/banner',
		'res/logo',
		'templates/signature_template.php',
		'templates/email_notice_template.php'

	];

	$file = get_image_path( 'banner' );
	if ( $file ) {
		if ( ! is_writable( $file ) ) {
			if ( file_exists( $file ) ) {
				$need_adjusting[] = $file;
			}
		}
	}

	$file = get_image_path( 'logo' );
	if ( $file ) {
		if ( ! is_writable( $file ) ) {
			if ( file_exists( $file ) ) {
				$need_adjusting[] = $file;
			}
		}
	}


	$root_path = realpath( dirname( __FILE__ ) );

	foreach ( $relative_files_to_check as $file_part ) {
		$file = $root_path . '/' . $file_part;
		if ( ! is_writable( $file ) ) {
			if ( file_exists( $file ) ) {
				$need_adjusting[] = $file;
			}
		}
	}

	foreach ( $need_adjusting as $issue ) {
		$issues[] = "$issue is not writable by php";
	}

	if ($_POST) {
	    if (isset($_POST['submit-signature-changes'])) {
	        if ( (!array_key_exists('domain-url',$_POST) ) || empty(trim($_POST['domain-url'])) ) {
	            throw new Exception("Need Domain URL Set");
            }

		    if ( (!array_key_exists('footer-template',$_POST) ) || empty(trim($_POST['footer-template'])) ) {
			    throw new Exception("Need Signature Template Set");
		    }

		    if ( (!array_key_exists('email-message',$_POST) ) || empty(trim($_POST['email-message'])) ) {
			    throw new Exception("Need Email Message Set");
		    }


		    if (isset($_FILES['logo-image']) && ($_FILES['logo-image']['size'] > 0)) {
			    $logo_file_array = Input::get('logo-image',Input::MUST_BE_FILE);
			    $name_of_upload = $logo_file_array['name'];
			    $new_logo_path = $logo_file_array['tmp_name'];
			    set_image_from_file('logo',$name_of_upload,$new_logo_path);
            }

		    if (isset($_FILES['banner-image']) && ($_FILES['banner-image']['size'] > 0)) {
			    $logo_file_array = Input::get('banner-image',Input::MUST_BE_FILE);
			    $name_of_upload = $logo_file_array['name'];
			    $new_logo_path = $logo_file_array['tmp_name'];
			    set_image_from_file('banner',$name_of_upload,$new_logo_path);
		    }

		    $email_message = $_POST['email-message'];
		    $email_path = $root_path . "/templates/email_notice_template.php";
		    $b_what = file_put_contents($email_path,$email_message);
		    if ($b_what === false) {
		        throw new Exception("Could not save $email_path");
            }


		    $signature_template = $_POST['footer-template'];
		    $signature_path = $root_path . "/templates/signature_template.php";
		    $b_what = file_put_contents($signature_path,$signature_template);
		    if ($b_what === false) {
			    throw new Exception("Could not save $signature_path");
		    }

		    replace_env_var('WEB_ROOT',$_POST['domain-url']);
		    header("Location: settings.php");
		    exit;

        }


		if (isset($_POST['submit-credential-changes'])) {


            if ( (!array_key_exists('service-account-json',$_POST) ) || empty(trim($_POST['service-account-json'])) ) {
	            throw new Exception("Need service-account-json Set");
            }

            if ( (!array_key_exists('web-auth-json',$_POST) ) || empty(trim($_POST['web-auth-json'])) ) {
	            throw new Exception("Need web-auth-json Set");
            }

            if ( (!array_key_exists('oauth-callback',$_POST) ) || empty(trim($_POST['oauth-callback'])) ) {
	            throw new Exception("Need oauth-callback Set");
            }

            if (isset($_POST['delete-credentials']) && !empty($_POST['delete-credentials'])) {
	            $credential_path = $root_path . "/config/credentials.json";
	            unlink($credential_path);
            }


			$service_account_json = $_POST['service-account-json'];
			$service_json_path = $root_path . "/config/service-account.json";
			$b_what = file_put_contents($service_json_path,$service_account_json);
			if ($b_what === false) {
				throw new Exception("Could not save $service_json_path");
			}


			$web_auth_json = $_POST['web-auth-json'];
			$web_auth_path = $root_path . "/config/web-auth.json";
			$b_what = file_put_contents($web_auth_path,$web_auth_json);
			if ($b_what === false) {
				throw new Exception("Could not save $web_auth_path");
			}

			replace_env_var('GOOGLE_WEB_AUTH_REDIRECT_URL',$_POST['oauth-callback']);

			header("Location: manual_oauth_setup.php");
			exit;

		}
    }







} catch ( Exception $e ) {
	$issues[] = $e->getMessage() . "\n" . $e->getTraceAsString();
} finally {
    try {


	    $oauth_callback       = getenv( 'GOOGLE_WEB_AUTH_REDIRECT_URL' );
	    $web_auth_json        = file_get_contents( $root_path . "/config/web-auth.json" );
	    $service_account_json = file_get_contents( $root_path . "/config/service-account.json" );

	    $domain_url      = getenv( 'WEB_ROOT' );
	    $footer_template = file_get_contents( $root_path . "/templates/signature_template.php" );
	    $email_message   = file_get_contents( $root_path . "/templates/email_notice_template.php" );
	    $logo_url        = get_image_url( 'logo' );
	    $banner_url      = get_image_url( 'banner' );
    } catch ( Exception $f ) {
	    $issues[] = $f->getMessage() . "\n" . $f->getTraceAsString();
    }
}

?>

    <!DOCTYPE html>
    <!--suppress JSCheckFunctionSignatures -->
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>GSuit Signature Settings</title>
        <style>
            /*noinspection CssUnusedSymbol*/
            .ck-editor__editable {
                min-height: 400px;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.3.1.min.js"
                integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
              integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
              crossorigin="anonymous">

        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"
              integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp"
              crossorigin="anonymous">

        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
                integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
                crossorigin="anonymous"></script>

        <!--	fontawesome!-->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css"
              integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt"
              crossorigin="anonymous">

        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/ace.js" ></script>
<!--        <script src="https://cdn.ckeditor.com/ckeditor5/11.0.1/classic/ckeditor.js"></script>-->
        <script src="https://cdn.ckeditor.com/4.10.0/standard/ckeditor.js"></script>
        <script>
            $(function() {
                // ClassicEditor
                //     .create( document.querySelector( '#email-message' ) )
                //     .then( editor => {
                //         console.log( editor );
                //     } )
                //     .catch( error => {
                //         console.error( error );
                //     } );


                CKEDITOR.replace( 'email-message', {
                    removeButtons: 'Redo,Undo',
                    height: '25em'
                } );

                var text ;
                var sig_editor = ace.edit("footer-template");
                sig_editor.getSession().setValue(<?= json_encode($footer_template); ?>);
                sig_editor.setTheme("ace/theme/dawn");
                sig_editor.getSession().setMode("ace/mode/php");
                sig_editor.getSession().setUseWrapMode(true);

                var sig_textarea = $('textarea[name="footer-template"]');
                text = sig_editor.innerHTML;
                sig_textarea.val(text);


                var web_auth_editor = ace.edit("web-auth-json");
                web_auth_editor.getSession().setValue(<?= json_encode($web_auth_json) ?>);
                web_auth_editor.setTheme("ace/theme/dawn");
                web_auth_editor.getSession().setMode("ace/mode/json");
                web_auth_editor.getSession().setUseWrapMode(true);

                var web_auth_textarea = $('textarea[name="web-auth-json"]');
                text = web_auth_editor.innerHTML;
                web_auth_textarea.val(text);



                var service_account_editor = ace.edit("service-account-json");
                service_account_editor.getSession().setValue(<?= json_encode($service_account_json) ?>);
                service_account_editor.setTheme("ace/theme/dawn");
                service_account_editor.getSession().setMode("ace/mode/json");
                service_account_editor.getSession().setUseWrapMode(true);

                var service_account_textarea = $('textarea[name="service-account-json"]');
                text = service_account_editor.innerHTML;
                service_account_textarea.val(text);



                $( "#form_certificates" ).submit(function(  ) {
                    service_account_textarea.val(service_account_editor.getSession().getValue());
                    web_auth_textarea.val(web_auth_editor.getSession().getValue());
                    sig_textarea.val(sig_editor.getSession().getValue());
                    return true;
                });

                $( "#form_signatures" ).submit(function(  ) {
                    service_account_textarea.val(service_account_editor.getSession().getValue());
                    web_auth_textarea.val(web_auth_editor.getSession().getValue());
                    sig_textarea.val(sig_editor.getSession().getValue());
                    return true;
                });




            });



        </script>
    </head>
    <body>
	<?php print_alerts( $messages, 'success' ) ?>
	<?php print_alerts( $issues, 'danger' ) ?>
    <div class="container">
        <div class="row" style="margin-bottom: 2em">
            <h1 class="display-1" style="text-align: left;margin-left: 0">GSuit Set Up</h1>
            <div class="panel panel-primary col-sm-12 col-md-5" >
                <div class="panel-heading">
                    <h2 class="panel-title">You can manage the Certificates Here</h2>

                </div>
                <div class="panel-body">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <h3 class="panel-title">Once you submit this you will be redirected to the manual_oauth_setup.php for this site to
                                complete and check</h3>
                        </div>
                    </div>
                    <form action="settings.php" method="post" id="form_certificates" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="service-account-json">Service Account Json</label>

                            <div class="form-control " id="service-account-json" style="height: 25em"
                            ></div>
                            <textarea name="service-account-json" style="display: none;"  title=""></textarea>
                            <span class="help-block">Copy the Service Account Json You Downloaded from Google</span>
                        </div>

                        <div class="form-group">
                            <label for="web-auth-json">Web OAUTH Json</label>
                            <div class="form-control " id="web-auth-json" style="height: 25em"
                            ></div>
                            <textarea name="web-auth-json" style="display: none;"  title=""></textarea>
                            <span class="help-block">Copy the OAUTH Json You Downloaded from Google</span>
                        </div>


                        <div class="form-group">
                            <label for="oauth-callback">OAUTH Callback</label>
                            <input value="<?= $oauth_callback ?>" type="url" class="form-control input-lg"
                                   id="oauth-callback" name="oauth-callback"
                                   placeholder="The url to this website's manual_oauth_setup.php">
                            <span class="help-block">The url to this website's manual_oauth_setup.php (https://yourdomain.com/manual_oauth_setup.php) </span>
                        </div>

                        <div class="form-group">
                            <label for="delete-credentials">Credentials</label>
                            <div class="checkbox">
                                <label>
                                    <input style="" type="checkbox" id="delete-credentials" name="delete-credentials"> Delete the Generated Credentials To Refresh Them
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="submit-credential-changes"
                                    class="btn btn-success btn-block full-width form-control" style="" value="1">
                                Update Credentials (Will be Redirected to Manual Oauth Setup)
                            </button>
                        </div>

                    </form>
                </div>
            </div>


            <div class="panel panel-primary col-sm-12 col-md-6 col-md-offset-1" >
                <div class="panel-heading">
                    <h2 class="panel-title">You can change the footer settings here</h2>
                </div>
                <div class="panel-body">
                    <form action="settings.php" method="post" id="form_signatures" enctype="multipart/form-data">


                        <div class="form-group">
                            <label for="domain-url">Domain URL</label>
                            <input value="<?= $domain_url ?>" type="url" class="form-control input-lg" id="domain-url"
                                   name="domain-url" autocomplete="url" placeholder="Full URL to the Site">
                            <span class="help-block">Enter the url for this web app </span>
                        </div>

                        <div class="form-group">
                            <label for="footer-template">Footer Template</label>
                            <div class="form-control " id="footer-template" style="height: 40em"
                                      ></div>
                            <textarea name="footer-template" style="display: none;"  title=""></textarea>
                            <span class="help-block">Create or Edit the Signature Template</span>
                        </div>

                        <div class="form-group">
                            <label for="email-message">Email Out Footer</label>
                            <textarea class="form-control " id="email-message" name="email-message" rows="15"
                                      placeholder="Edit the Email Used to Send Out Footers"><?= $email_message ?></textarea>
                            <span class="help-block">Create or Edit the Email used to to send out footers</span>
                        </div>


                        <div class="form-group">
                            <label for="logo-image">Logo</label>
                            <input id="logo-image" name="logo-image" type="file" class="file"
                                   placeholder="The Logo Image">
                            <span class="help-block">Upload the Logo Here</span>
                            <img src="<?= $logo_url ?>">
                        </div>

                        <div class="form-group">
                            <label for="banner-image">Banner</label>
                            <input id="banner-image" name="banner-image" type="file" class="file"
                                   placeholder="The Footer Image">
                            <span class="help-block">Upload the Banner Here</span>
                            <img src="<?= $banner_url ?>">
                        </div>



                        <div class="form-group">
                            <button type="submit" name="submit-signature-changes"
                                    class="btn btn-primary btn-block full-width form-control" style="" value="1">
                                Change the Signature Settings
                            </button>
                        </div>


                    </form>
                </div>
            </div>
        </div>


    </div>

    </body>
    </html>

<?php
//settings page
//do a check for writable files and folders


//
//update and change banner, logo, (they will be saved in a new folder called domain)
//their path will be given in a php variable when template is called
//
//update and change domain via an input box:
//  rewrite env file
//	  domain will change GOOGLE_WEB_AUTH_REDIRECT_URL in env
//	  domain itself will be set to DOMAIN in env
//
//update and change WEB_ROOT same as domain

//change oauth settings:
//  upload web-auth.json - put in config/web-auth.json
//  upload service-account.json - put in config/service-account.json
//  * config/credentials.json and make the page visit manual setup when these change
//ace template for html, with live preview next to it
//
//ckeditor email notice template
//
//list of to do
//1) change banner and logo to be saved in a way that does not depend on their name : they will each have a folder, and be the
//only contents of that folder

//$file_path = get_image_path('banner');
//replace_env_var('GOOGLE_WEB_AUTH_REDIRECT_URL','xxxxx-yyyyy');





