<?php

// Database names and locations. The $dbxxpending must be put to a folder that is writable in order to process incoming virtual mails
$dbxx = "shde.db";
$dbxxpending = "/var/www/somewhere/pendingshde.db"; // must be a writable folder from all processes

// Site parameters
$sitedomain = "example.org";
$topmail = 'shde@example.org';
$ct = "https://www.example-org/shde";
$title = "ΣΗΔΕ";

// Superadmin AFM
$superadminuid = "000000000";

// If this is 1, unknown logging users can request an organization creation
$CanHostOthers = 0;

// If login_taxis is not empty, then it points to an URL that will authenticate with taxis and return "shde_username" "shde_lastname" "shde_firstname" in the $_SESSION
$login_taxis = '';

// If this is 1, then demo logins are allowed 
$login_demo = 1;

// If this is 1, biometric key creations and logins are allowed. This is required for classified documents.
$login_bio = 1;

// If this is not empty, then it points to an URL that will authenticate with PSD and return "shde_username" "shde_lastname" "shde_firstname" in the $_SESSION
$login_psd = '';


/*
    openssl ecparam -genkey -name prime256v1 -out private_key.pem
    openssl ec -in private_key.pem -pubout -outform DER|tail -c 65|base64|tr -d '=' |tr '/+' '_-' >> private_key.txt
    openssl ec -in private_key.pem -outform DER|tail -c +8|head -c 32|base64|tr -d '=' |tr '/+' '_-' >> public_key.txt
    cat public_key.txt
    cat private_key.txt
    rm -f private_key.txt
    rm -f private_key.pem
    rm -f public_key.txt
*/

// Public/Private key for the notification API
$pushpriv = '';
$pushpub = '';


// MAIL PARAMETERS for DKIM
define('MAIL_DOMAIN', $sitedomain);
define('MAIL_SELECTOR', 'dkim');
define('MAIL_RSA_PUBL',
'-----BEGIN PUBLIC KEY-----
....
-----END PUBLIC KEY-----');
define('MAIL_RSA_PRIV',
'-----BEGIN RSA PRIVATE KEY-----
....
-----END RSA PRIVATE KEY-----
');
define('MAIL_RSA_PASSPHRASE', '');
define('MAIL_IDENTITY', "shde@$sitedomain");



// ptype: 0 in 1 out
// txt:  description
// user: list of receipients or senders as text
// level: classification level (0,1,2,3)

// returns array of n (prot), id (database ID), date, level
function NewProtocol($txt,$ptype,$user,$level)
{
    $ar = array("n" => "100", "id" => "1", "t" => time(),"l" => $level);
    return $ar;
}

// Don't change these
$wshost = "wss://www.$sitedomain/ws/";
$not_answer = "/shde/notify.php?pushanswer=1";
$not_js =  "/shde/notify2.js";
