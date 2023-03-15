<?php

use PHPMailer\PHPMailer\PHPMailer;
$lastmailerror = "";
function mail2($mailfrom = array("name" => "Me","mail" => "me@me.com"),$receipients = array(),$subject = "Subject",$htmlmessage = "",$textmessage = "",$smtp = array(),$replyto = array(),$dkim = array(),$ats = array(),$bcert = "",$pkpass = "",$ccs = array(),$bccs = array())
{
    global $lastmailerror;
    $certFile = "";
    $privateFile = "";
    $chainFile = "";
	$r = 0;
    try
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";

	    $mail->setFrom($mailfrom["mail"],$mailfrom["name"]);
	    if (count($replyto) != 2)
		    $mail->addReplyTo($mailfrom["mail"],$mailfrom["name"]);
	    else
		    $mail->addReplyTo($replyto["mail"],$replyto["name"]);


        // SMTP?
        if (count($smtp) >= 2)
	    {
		    $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->SMTPKeepAlive = true;
            $mail->Host = $smtp['host'];
            $mail->Port = (int)$smtp['port'];
            if (array_key_exists("security",$smtp))
		        $mail->SMTPSecure = $smtp['security'];
            if (array_key_exists("pass",$smtp))
            {
                $mail->SMTPAuth = true;
                $mail->Username = $smtp['user'];
                $mail->Password = $smtp['pass'];
            }
	    }
        else
		    $mail->isMail();

	    foreach($receipients as $rep)
	    {
		    $mail->addAddress($rep['mail'], $rep['name']);
	    }
		
		foreach($ccs as $rep)
	    {
		    $mail->addCC($rep['mail'], $rep['name']);
	    }
		
		foreach($bccs as $rep)
	    {
		    $mail->addBCC($rep['mail'], $rep['name']);
	    }

        $mail->Subject = $subject;
	    if (!strlen($htmlmessage))
	    {
		    $mail->Body = $textmessage;
		    $mail->isHTML(false);
	    }
	    else
		    {
		    $mail->Body = $htmlmessage;
		    $mail->isHTML(true);
            if (strlen($textmessage))
                $mail->AltBody  =  $textmessage;
		    }
	    if (count($dkim) >= 2)
	    {
		    $mail->DKIM_domain = $dkim["domain"];
		    $mail->DKIM_private = $dkim["priv"];
		    $mail->DKIM_selector = $dkim["selector"];
		    $mail->DKIM_passphrase = $dkim["pass"];
		    $mail->DKIM_identity = $dkim["id"];

	    }

	    foreach($ats as $at)
        {
            $mail->addAttachment($at['path'],$at['name'],$at['encoding'],$at['mime']);
        }


        if (strlen($bcert))
        {
            // Extract the private key
            $certs = array();
            $pfx = base64_decode($bcert);
            if (!openssl_pkcs12_read($pfx,$certs,$pkpass))
            {
                return 0;
            }

            $certdata = openssl_x509_parse($certs['cert']);

            if( $certdata['validFrom_time_t'] > time() || $certdata['validTo_time_t'] < time() )
                return 0;


            //Configure message signing (the actual signing does not occur until sending)
            $certFile = tempnam(sys_get_temp_dir(), 'CERT');
            $privateFile = tempnam(sys_get_temp_dir(), 'PKEY');
            $chainFile = tempnam(sys_get_temp_dir(), 'CHAIN');

            file_put_contents($certFile,$certs['cert']);
            file_put_contents($privateFile,$certs['pkey']);
            file_put_contents($chainFile,$certs['extracerts'][0]);

            $mail->sign(
                $certFile,
                $privateFile,
                $pkpass,
                $chainFile
            );
        }

        $r =  $mail->send();
     }
    catch (phpmailerException $e) {
        $lastmailerror = $e->errorMessage();
		//print_r($lastmailerror);
    }
    if (file_exists($chainFile))
    {
        unlink($chainFile);
        unlink($certFile);
        unlink($privateFile);
    }
/*
	$arr = get_defined_vars();
	printf("<pre>");
	print_r($arr);
	printf("</pre>");
	die;
*/

    if (!$r)
		return 0;
    return 1;
}
