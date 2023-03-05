<?php

require_once "functions.php";
if (!$u)
    diez();


if (!array_key_exists("oid",$req))
    diez();

$str = OidOrgchartStdclass($req['oid']);
$j = json_encode($str);
printr($str);


$authorization = '';
$c = curl_init();
$st = ShdeUrl($req['oid']) . '/orgchart';
curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($c, CURLOPT_AUTOREFERER,    1);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
$authorization = GetBearer($req['oid']);
if ($authorization == '')
    diez();
curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',$authorization));
curl_setopt($c, CURLOPT_URL, $st );
curl_setopt($c, CURLOPT_REFERER, $siteroot);
curl_setopt($c, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($c,CURLOPT_POSTFIELDS,$j);
$r = curl_exec($c);
$arx2 = json_decode($r,true);

printr($arx2);