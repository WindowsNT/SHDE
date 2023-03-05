<?php

require_once "functions.php";
require_once "vendor/autoload.php";
if (!array_key_exists("clsid",$req))
    die(-1);

$dr = QQ("SELECT * FROM DOCUMENTS WHERE CLSID = ?",array($req['clsid']))->fetchArray();
if (!$dr)
    die(-2);

$msg = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($dr['ID']))->fetchArray();
if (!$msg)
    die(-2);

$body = file_get_contents("php://input");
if (strlen($body) < 10)
    die(-3);

// Check if a signature is there    
if (strpos($body, "adbe.pkcs7.detached") === false && strpos($body,"ETSI.CAdES.detached") === false) 
    die(-4);

// Check if it's the same PDF
$SkipSame = 0;
if ($dr['ADDEDSIGNERS'])
    {
        $extrasigs = explode(",",$dr['ADDEDSIGNERS']);
        if (count($extrasigs) > 0)
        $SkipSame = 1;
    }

if ($SkipSame = 0)
{
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseContent($body);
    $details = $pdf->getDetails();
    if (array_key_exists("Producer",$details) && strstr($details['Producer'],"AdES Tools"))
    {

    }
    else
    {
        if (!array_key_exists("Keywords",$details))
            die(-5);
        if ($details['Keywords'] != $dr['CLSID'])
            die(-6);
    }    
}

if ($dr['CLASSIFIED'] > 0)
{
    $pwd = PasswordFromSession($dr['ID']);
    if ($pwd === FALSE)
        $pwd = $req['pwd'];
    if ($pwd === FALSE)
        die(-7);
    $body = ed($body,$pwd,'e');
}

QQ("UPDATE MESSAGES SET SIGNEDPDF = ? WHERE ID = ?",array($body,$msg['ID'])); 
die("OK SUCCESS");




