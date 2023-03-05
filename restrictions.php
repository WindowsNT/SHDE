<?php

require_once "functions.php";
require_once "output.php";
if (!$u)
    diez();

if (!array_key_exists("eid",$req))
    $req['eid'] = 0;
if (!array_key_exists("oid",$req))
    $req['oid'] = 0;

if (array_key_exists("run",$req))
{
    if ($req['oid'] > 0)
        QQ("UPDATE ORGANIZATIONS SET LIMITCODES = ? WHERE ID = ?",array($req['eps'],$req['oid']));
    if ($req['eid'] > 0)
        QQ("UPDATE ENDPOINTS SET LIMITCODES = ? WHERE ID = ?",array($req['eps'],$req['eid']));
    redirect("index.php");
    return;
}
PrintHeader('index.php');

$q = null;
if ($req['eid'] > 0)
{
    $q = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($req['eid']))->fetchArray();
}
if ($req['oid'] > 0)
{
    $q = QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($req['oid']))->fetchArray();
}

if (!$q)
    die;

?>
<form method="POST" action="restrictions.php">
    <input name="eid" type="hidden" value="<?= $req['eid'] ?>" >
    <input name="oid" type="hidden" value="<?= $req['oid'] ?>" >
    <input name="run" type="hidden" value="1" >
    Περιορισμοί στους παραλήπτες:<br><br>
    <?php
    echo EchoShdePicker(random_int(100000,999999),"eps",$q['LIMITCODES'] ? explode(",",$q['LIMITCODES']) : array(),0);
    ?>
    <br><br><hr>
    <button class="button is-primary">Υποβολή</button>
</form>
