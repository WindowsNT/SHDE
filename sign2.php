<?php


$base = 'https://sapi.mindigital-shde.gr/Sign/Api';
//  $base = 'https://sapi-dev.mindigital-shde.gr/Sign/Api';


function arrayToXml($array, &$xml){
    foreach ($array as $key => $value) {
        if(is_int($key)){
            $key = "e";
        }
        if(is_array($value)){
            $label = $xml->addChild($key);
            arrayToXml($value, $label);
        }
        else {
            $xml->addChild($key, $value);
        }
    }
}

class popl
{
    function login($user,$pass)
    {   
/*
        $folder="INBOX";
        $ssl = true;
        $host = 'mail.sch.gr';
        $port = 995;
        $ssl=($ssl==false)?"/novalidate-cert":"";
        return (imap_open("{"."$host:$port/pop3$ssl"."}$folder",$user,$pass));
*/        
        $folder="INBOX";
        $ssl = false;
        $host = 'mail.sch.gr';
        $port = 110;
        $ssl=($ssl==false)?"/novalidate-cert":"";
        return (imap_open("{"."$host:$port/pop3$ssl"."}$folder",$user,$pass));
    }


    function close($c)
    {
        return imap_close($c);
    }
    function expu($c)
    {
        return imap_expunge($c);
    }

    function stat($connection)        
    {
        $check = imap_mailboxmsginfo($connection);
        return ((array)$check);
    }
    function list($connection,$message="")
    {
        if ($message)
        {
            $range=$message;
        } else {
            $MC = imap_check($connection);
            $range = "1:".$MC->Nmsgs;
        }
        $response = imap_fetch_overview($connection,$range);
        foreach ($response as $msg) $result[$msg->msgno]=(array)$msg;

        return $result;
    }
    function retr($connection,$message)
    {
        return(imap_fetchheader($connection,$message,FT_PREFETCHTEXT));
    }
    function msgx($connection,$message)
    {
        return(imap_body($connection,$message));
    }
    function dele($connection,$message)
    {
        return(imap_delete($connection,$message));
    }

};

$req = array_merge($_GET,$_POST);
if ($req['f'] == '')
    $req = json_decode(file_get_contents('php://input'), true);

if ($req['f'] == "test")
{
    $c = curl_init();
    $st = $base . '/status';
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_URL, $st );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    $r = curl_exec($c);
    header("Content-Type: application/json");
    print_r($r);
    die;
}

if ($req['f'] == "otp")
{   
    $c = curl_init();
    $st = $base . '/RequestOTP';
    $params = array("Username" => $req['u'],"Password" => $req['p']);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',));
    curl_setopt($c, CURLOPT_URL, $st );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($params));
    $r = curl_exec($c);
    header("Content-Type: application/json");
    print_r($r);
    die;
}

function GetCerts($u,$p)
{
    global $siteroot;
    global $base;
    $c = curl_init();
    $st = $base . '/Certificates';
    $params = array("Username" => $u,"Password" => $p);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',));
    curl_setopt($c, CURLOPT_URL, $st );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($params));
    $r = curl_exec($c);
    return $r;
}


if ($req['f'] == "certs")
{   
    $r2 = GetCerts($req['u'],$req['p']);
    $r2a = json_decode($r2,true);
    $a['Certs'] = $r2a;
    $xml = new SimpleXMLElement('<root/>');
    arrayToXml($a, $xml);
    header("Content-Type: application/xml");
    echo $xml->asXML();
 
// header("Content-Type: application/json");
  //  print_r($r);
    die;
}


if ($req['f'] == "sign")
{   
    $c = curl_init();
    $st = $base . '/SignBuffer';
    $otp = $req['o'];
    if (array_key_exists("hb64",$req))
        $params = array("Username" => $req['u'],"Password" => $req['p'],"SignPassword" => $otp,"BufferToSign" => $req['hb64']);
    else
    if (array_key_exists("hb",$req))
        $params = array("Username" => $req['u'],"Password" => $req['p'],"SignPassword" => $otp,"BufferToSign" => base64_encode($req['hb']));
    else
        $params = array("Username" => $req['u'],"Password" => $req['p'],"SignPassword" => $otp,"BufferToSign" => base64_encode(hash("sha256",$req['b'],true)));
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',));
    curl_setopt($c, CURLOPT_URL, $st );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($params));
    $r = curl_exec($c);
    header("Content-Type: application/json");
    print_r($r);
    die;
}

if ($req['f'] == "sign2")
{
    $pop = new popl;
    $imap = $pop->login($req['pu'],$req['pp']);
    $statbefore = ($pop->stat($imap));
    $listbefore = $pop->list($imap);
    $pop->close($imap);

    // Ask the OTP
    $c = curl_init();
    $st = $base . '/RequestOTP';
    $params = array("Username" => $req['u'],"Password" => $req['p']);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',));
    curl_setopt($c, CURLOPT_URL, $st );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($params));
    $r = curl_exec($c);
    $arx = json_decode($r,true);
    if ($arx['Outcome'] != "0")
        die('{"s":"-1","m":"OTP would not be generated from SHDE"}');

    $otp = '';
    for($tries = 0 ; $tries < 5 ; $tries++)
    {
        sleep(5);
        $pop = new popl;
        $imap = $pop->login($req['pu'],$req['pp']);
        $statafter = ($pop->stat($imap));
        if ($statbefore['Nmsgs'] != $statafter['Nmsgs'] - 1)
            continue;

        $listafter = $pop->list($imap);

        $m = $statbefore['Nmsgs'] + 1;
        $msg = $pop->retr($imap,$m);
        $msg2 = $pop->msgx($imap,$m);

        $msg3 =base64_decode($msg2);
        preg_match('/<strong>(.*?)<\/strong>/s', $msg3, $match);
        $otp = $match[1];
        $pop->dele($imap,$m);
        $pop->expu($imap);
        $pop->close($imap);
        break;
    }


    if ($otp == '')
        die('{"s":"-2","m":"Mail cannot be read for OTP"}');

    $c = curl_init();
    $st = $base . '/SignBuffer';

    if (array_key_exists("hb64",$req))
        $params = array("Username" => $req['u'],"Password" => $req['p'],"SignPassword" => $otp,"BufferToSign" => $req['hb64']);
    else
    if (array_key_exists("hb",$req))
        $params = array("Username" => $req['u'],"Password" => $req['p'],"SignPassword" => $otp,"BufferToSign" => base64_encode($req['hb']));
    else
        $params = array("Username" => $req['u'],"Password" => $req['p'],"SignPassword" => $otp,"BufferToSign" => base64_encode(hash("sha256",$req['b'],true)));
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',));
    curl_setopt($c, CURLOPT_URL, $st );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($params));
    $r = curl_exec($c);
    $a = json_decode($r,true);
    if ($a['Success'] != "true")
        die;
    $r2 = GetCerts($req['u'],$req['p']);
    $r2a = json_decode($r2,true);
    $a['Certs'] = $r2a;

//    ini_set('display_errors', 1); error_reporting(E_ALL);
    $xml = new SimpleXMLElement('<root/>');
    arrayToXml($a, $xml);
    header("Content-Type: application/xml");
    echo $xml->asXML();
    
//    header("Content-Type: application/json");
 //   echo json_encode($a);
}
