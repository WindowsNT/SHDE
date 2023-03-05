<?php

require_once "functions.php";

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];
foreach(explode(",",$req['docs']) as $did)
{
    if (UserAccessDocument($did,$u->uid) != 2)
        continue;
    DeleteDocument($did);
}  
redirect($whereret);
