# gsuit

a few notes
config/service-account.json

config/web-auth.json

are not checked in

web-auth.json looks like this, and is the downloaded json file for an oath web app key from google
{"web":
{"client_id":"xxxx","project_id":"xxxx",
"auth_uri":"https://accounts.google.com/o/oauth2/auth",
"token_uri":"https://accounts.google.com/o/oauth2/token",
"auth_provider_x509_cert_url":"https://www.googleapis.com/oauth2/v1/certs",
"client_secret":"xxxxx","redirect_uris":["http://localhost/gsuit/manual_oauth_setup.php","xxxxx"]
}}

service-account.json looks like this and is the downloaded json file from google for a service account

{
  "type": "service_account",
  "project_id": "xxxxx",
  "private_key_id": "xxxxx",
  "private_key": "-----BEGIN PRIVATE KEY-----\nxxxxx\n-----END PRIVATE KEY-----\n",
  "client_email": "yyyyy",
  "client_id": "xxxxx",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://accounts.google.com/o/oauth2/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "xxxxx"
}


the database structure is in config, and the tables and some sample data is already there


the spreadsheet header stuff is 

    First name
	last Name	
	company name	
	function	
	Email	
	Mobile	Tel 
	Office	
	address line 1	
	address line2	
	postal code	
	city	
	website	
	twitter	
	linkedin	
	facebook