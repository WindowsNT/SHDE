<?php

require_once "functions.php";
if (!$u)
    diez();

require_once "output.php";
PrintHeader();

if (!array_key_exists("q_topic",$req))
    $req['q_topic'] = '';
if (!array_key_exists("q_text",$req))
    $req['q_text'] = '';
$oids = array(0);
if (array_key_exists("q_oids",$req))
    $oids = explode(",",$req['q_oids']);
$eids = array(0);
if (array_key_exists("q_eids",$req))
    $eids = explode(",",$req['q_eids']);
$fids = array(0);
if (array_key_exists("q_fids",$req))
    $fids = explode(",",$req['q_fids']);
$wids = array(0);
if (array_key_exists("q_wids",$req))
    $wids = explode(",",$req['q_wids']);
?>

<form method="POST" action="eggr.php" >
    <input name="q" type="hidden" value="1" />

    Φορεας:
    <?= PickOrganization("q_oids[]",$oids,1,$u->uid,0,1); ?> <br><br>

    Endpoint:
    <?= PickEP("q_eids[]",$eids,1,$u->uid,0,0,2,1); ?> <br><br>

    Φάκελοι:
    <?= PickFolder($eids[0],"q_fids[]",$fids,$u->uid,1,$oids[0],2); ?> <br><br>

    Συντάκτες:
    <?= PickUser("q_wids[]",$wids,1,$oids,1,$u->uid); ?> <br><br>

    Θέμα:
    <input name="q_topic" type="text" class="input" value="<?= $req['q_topic'] ?>" />
<br><br>
    Κείμενο:
    <input name="q_text" type="text" class="input" value="<?= $req['q_text'] ?>" />

    <br><br>
    <button class="autobutton button is-primary">Αναζήτηση</button>
</form>
