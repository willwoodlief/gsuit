<?php
set_time_limit( 0 );
if ($_POST) {

	if (isset($_POST['download-all-credentials'])) {
		//get all the signatures
		require_once realpath( dirname( __FILE__ ) ) . "/vendor/autoload.php";
		require_once realpath( dirname( __FILE__ ) ) . "/lib/helpers.php";
# here, if something is called, we get the post vars and we update the user signature
# then we return json status of the operation, along with any message
//
		$not_included = [];
		$debug_messages = [];
		$action_hash = [];
		try {

			$directory_client    = get_directory_client( null, $debug_messages, true );
			$user_hash           = [];
			$user_data           = get_user_info( $directory_client, $user_hash );
			$current_spreadsheet = get_current_spreadsheet_info();
			$data                = $current_spreadsheet['data'];
			$gmail_client        = create_email_client();
			$action_hash         = [];
			$send_as_email       = $old_sig = null;
			foreach ( $data as $row ) {
				$primary_email = $row['email'];
				if ( ! isset( $user_hash[ $primary_email ] ) ) {
					$not_included[] = $primary_email;
					continue; //some things in the spreadsheet may not be on the gsuit
				}
				//get alias
				$gmail = get_gmail_object( $gmail_client, $primary_email, $send_as_email, $old_sig );
				$sig   = generate_footer( $row );
				set_email_signature( $gmail, $sig, $primary_email, $send_as_email );
				$action_hash[] = [ 'alias' => $send_as_email, 'sig' => $sig, 'email' => $primary_email ];
			}

			//make html file, first in string
			$html = '';
			foreach ($action_hash as $action) {
				$node = "<a href='mailto:" . $action['email'] . "'>".$action['email']."</a><br><br>\n\n";
				$node .= $action['sig'];
				$node .= "\n<br><br><hr>";
				$html .= $node;
			}


			$the_name = 'all_account_signatures.html';
			//save as tmp file
			$file      = tmpfile();
			$file_path = stream_get_meta_data( $file )['uri'];
			fwrite( $file, $html );
			fseek( $file, 0 );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: text/html' );
			header( 'Content-Disposition: attachment; filename=' . $the_name );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $file_path ) );
			header( 'X-Accel-Buffering: no' );
			//ob_clean();
			//flush();
			set_time_limit( 0 );
			while ( ! feof( $file ) ) {
				print( @fread( $file, 1024 * 8 ) );
				ob_flush();
				flush();
			}
			exit;

		}
		catch (Exception $e) {
			JsonHelpers::printErrorJSONAndDie($e->getMessage() . "\n<br>\n" . $e->getTraceAsString());
		}
	}

	if (isset($_POST['download-credentials'])) {
		try {
			//download the zip
			if ( ! isset( $_POST['email'] ) ) {
				throw new Exception( "Need to have email[] input when using this form" );
			}

			if ( ! isset( $_POST['sig'] ) ) {
				throw new Exception( "Need to have sig[] input when using this form" );
			}

			if ( ! is_array( $_POST['email'] ) ) {
				throw new Exception( "email param needs to be an array" );
			}

			if ( ! is_array( $_POST['sig'] ) ) {
				throw new Exception( "sig param needs to be an array" );
			}

			if ( sizeof( $_POST['email'] ) === 1 && sizeof( $_POST['sig'] ) === 1 ) {
				//download this as html
				$the_sig  = base64_decode( $_POST['sig'][0] );
				$the_name = str_replace( '@', '.', $_POST['email'][0] );
				$the_name .= '.html';
				//save as tmp file
				$file      = tmpfile();
				$file_path = stream_get_meta_data( $file )['uri'];
				fwrite( $file, $the_sig );
				fseek( $file, 0 );
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: text/html' );
				header( 'Content-Disposition: attachment; filename=' . $the_name );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Pragma: public' );
				header( 'Content-Length: ' . filesize( $file_path ) );
				header( 'X-Accel-Buffering: no' );
				//ob_clean();
				//flush();

				while ( ! feof( $file ) ) {
					print( @fread( $file, 1024 * 8 ) );
					ob_flush();
					flush();
				}
				exit;

			}
		}
		catch (Exception $e) {
				JsonHelpers::printErrorJSONAndDie($e->getMessage() . "\n<br>\n" . $e->getTraceAsString());
		}




	}

}