<?php

require_once "functions.php";

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];


foreach(explode(",",$req['docs']) as $did)
{
    $dr = DRow($did);
     if (!$dr['SHDEPROTOCOL'])   continue;
    if ($dr['SHDEPROTOCOL'] == '')   continue;
    if (UserAccessDocument($did,$u->uid) == 0)
        continue;
    $ERow = EPRow($dr['EID']);
    $FRow = FRow($ERow['OID']);

    $c = curl_init();
    $statusurl = ShdeUrl($FRow['ID']).'/receipts/'.$dr['SHDERECEIPT'];
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_URL, $statusurl );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    $authorization = GetBearer($FRow['ID']);
    if ($authorization == '')
        return false;
    curl_setopt($c, CURLOPT_HTTPHEADER, array(
        $authorization  
        ));
    $rs = curl_exec($c);
    $j = json_decode($rs);

    $c = curl_init();
    $statusurl = ShdeUrl($FRow['ID']).'/documents/'.$dr['SHDEPROTOCOL'].'/status';
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_URL, $statusurl );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    $authorization = GetBearer($FRow['ID']);
    if ($authorization == '')
        return false;
    curl_setopt($c, CURLOPT_HTTPHEADER, array(
        $authorization  
        ));
    $rs = curl_exec($c);
    $j = json_decode($rs);
    
    QQ("UPDATE DOCUMENTS SET SHDECHECKSENT = ? WHERE ID = ?",array($rs,$did));
}  

redirect($whereret);
