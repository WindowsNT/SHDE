<?php

require_once "functions.php";



$ret = $req['ret'];
$oid = $req['oid'];
$a = UserAccessOID($oid,$u->uid);
if ($a == 0)
{
    redirect($ret);
    die;
}
$r = QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($oid))->fetchArray();
if (!$r)
    diez();

$loge = sprintf("shde_login_%s",$r['ID']);
$_SESSION[$loge] = array();

$jc = 'SHDECLIENT';
$js = 'SHDESECRET';
if ($r['SHDEPRODUCTION'] == 0)
{
    $jc = 'SHDECLIENT2';
    $js = 'SHDESECRET2';    
}
$params = array("SectorCode" => $r['SHDECODE'], "ClientID" =>  $r[$jc], "ClientSecret" => $r[$js]);

if (array_key_exists("secret",$req))
    $params["ClientSecret"] = $req['secret'];
if ($params["ClientSecret"] == "")
{
    require_once "output.php";
    PrintHeader();
    ?>
    <h3>Παρακαλώ δώστε τα στοιχεία  για login στο ΚΣΗΔΕ</h3>:
        <form method="POST" action="shdeauth.php" autocomplete="new-password">
    <input type="hidden" name="oid" value="<?= $req['oid'] ?>" />
    <input type="hidden" name="ret" value="<?= $req['ret'] ?>" />
    ΚΣΗΔΕ Όνομα Τομέα: <br>
    <input type="text" class="input" name="code" value="<?= $r['SHDECODE']?>" readonly/>
    <br><br>
    ΚΣΗΔΕ Client ID: <br>
    <input type="text" class="input" name="client" value="<?= $r[$jc]?>"  readonly>
    <br><br>
    ΚΣΗΔΕ Secret: <br>
    <input type="password" class="input" name="secret" value="<?= $r[$js]?>"  autocomplete="new-password" autofocus>
    <br><br>
    <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="index.php" class="button autobutton is-danger">Πίσω</button>
    <?php
    die;
}

$c = curl_init();
$oid = $r['ID'];
$base = ShdeUrl($oid) . '/authenticate/';
curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($c, CURLOPT_AUTOREFERER,    1);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',));
curl_setopt($c, CURLOPT_URL, $base );
curl_setopt($c, CURLOPT_REFERER, $siteroot);
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($params));
$r = curl_exec($c);
$arx = json_decode($r,true);
if (array_key_exists("AccessToken",$arx))
{
    $_SESSION[$loge]['AccessToken'] = $arx['AccessToken']; 
    $_SESSION[$loge]['TokenType'] = $arx['TokenType']; 
    $_SESSION[$loge]['ExpiresOn'] = $arx['ExpiresOn']; 


    // Notification
    if (0)
    {
        $authorization = "Authorization: Bearer ".$_SESSION[$loge]["AccessToken"]; // Prepare the authorisation token
        $c = curl_init();
        $base = ShdeUrl($oid) . '/notifications/subscribeForPush';
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, CURLOPT_AUTOREFERER,    1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',$authorization));
        curl_setopt($c, CURLOPT_URL, $base );
        curl_setopt($c, CURLOPT_REFERER, $siteroot);
        curl_setopt($c, CURLOPT_POST, true);
        $params2 = array("SectorCode" => $params['SectorCode'], "EventDeliveryUrl" =>  $siteroot.'/ep.php');
        $ue = json_encode($params2);
        $params3 = array("ValidationCode" => guidv4(),"ValidationUrl" =>  $siteroot.'/ep.php');
        $fields = array('subscriptionData' => $ue,"Model" => json_encode($params3));
        curl_setopt($c,CURLOPT_POSTFIELDS,$fields);
        $r3 = curl_exec($c);
        $arx3 = json_decode($r3,true);
    }

    // Notification
    if (1)
    {
        $authorization = "Authorization: Bearer ".$_SESSION[$loge]["AccessToken"]; // Prepare the authorisation token
        $c = curl_init();
        $base = ShdeUrl($oid) . '/notifications';
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, CURLOPT_AUTOREFERER,    1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json',$authorization));
        curl_setopt($c, CURLOPT_URL, $base );
        curl_setopt($c, CURLOPT_REFERER, $siteroot);
        $r3 = curl_exec($c);
        //$r3 = '{"results":[{"NotificationId":"2023275863","Type":3,"SenderSectorCode":40151,"RecipientSectorCode":8200667,"DocumentProtocolNo":"20232006670000267024","VersionNumber":1,"DateCreated":"2023-12-04T13:58:41.49","MetadataJson":{"Status":"3","Departments":"0"},"Links":[{"Rel":"status","Href":"https://sddd.mindigital-shde.gr/api/v1/documents/20232006670000267024/status?version=1","Method":"GET"}]}],"NextPage":null,"PreviousPage":null}';
        $arx3 = json_decode($r3,true);


        if (count($arx3['results']))
        {
            $url = 'checksent.php?docs=';
            $_SESSION['notif'] = sprintf('<xmp>%s</xmp>',print_r($arx3,true));
            foreach($arx3['results'] as $res)
            {
                $typ = (int)$res['Type'];
                if ($typ == 3)
                {
                    // Document update
                    $q1 = QQ("SELECT * FROM DOCUMENTS WHERE SHDEPROTOCOL = ?",array($res['DocumentProtocolNo']))->fetchArray();
                    if ($q1)
                    {
                        $url .= $q1['ID'];
                        $url .= ',';
                    }
                }
            }
            $ret = $url;
        }
    }
}

else //$arx['HttpStatus'] != 200)
{
    $_SESSION[$loge]['SideTrackingId'] = $arx['SideTrackingId']; 
    $_SESSION[$loge]['HttpStatus'] = $arx['HttpStatus']; 
    $_SESSION[$loge]['ErrorMessage'] = $arx['ErrorMessage']; 
    QQ("INSERT INTO LOGS (OID,DATE,DESCRIPTION) VALUES(?,?,?)",array($oid,time(),sprintf("Login error in SHDE: %s",serialize($arx))));
}

QQ("DELETE FROM LOGS WHERE DATE < ?",array(time() - 262974383));
redirect($ret);
