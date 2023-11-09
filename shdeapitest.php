<?php

require_once "functions.php";


if (array_key_exists("url",$_GET))
{
    $FRow = FRow(1);
 
    $c = curl_init();
    $statusurl = $_GET['url'];
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_URL, $statusurl );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    $authorization = GetBearer($FRow['ID']);
    curl_setopt($c, CURLOPT_HTTPHEADER, array(
        $authorization  
        ));
    $rs = curl_exec($c);
    $j = json_decode($rs);
    printdie($j);
}

require_once "output.php";
?>
<div class="content" style="margin: 20px;">
<form method="GET" action="shdeapitest.php">
    <input class="input" name="url" />
    <br><br><br>
    <button class="button is-success is-small">Submit</button>
</form>
</div>