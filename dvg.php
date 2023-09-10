<?php

require_once "functions.php";
if (!$u)
    diez();

$did = $req['did'];
$mid = 0;
if (array_key_exists("mid",$req))
    $mid = $req['mid'];
$dr = DRow($did,1);
if (UserAccessDocument($did,$u->uid) != 2)
    diez();

$erow = EPRow($dr['EID']);
$frow = FRow($erow['OID']);
$basedvgurl = 'https://diavgeia.gov.gr/opendata';
if ($frow['DVGID'] == '10599_api' && $frow['DVGPASS'] == 'User@10599')
    $basedvgurl = 'https://test3.diavgeia.gov.gr/luminapi/opendata';

function PostDvg()
{
    global $did,$mid,$erow,$frow,$dr,$basedvgurl;

    $c = curl_init();
    $st = $basedvgurl.'/decisions';
    $authorization = sprintf("");

    curl_setopt($c, CURLOPT_USERPWD, $frow['DVGID']  . ":" . $frow['DVGPASS'] );      
    curl_setopt_array($c, array(
        CURLOPT_URL => $st,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "Content-Type: multipart/form-data",
        ),
    ));

/*
        $tx = tempnam("/tmp","prf");
        file_put_contents($tx,$msgr['MSG']);
        $fields = array(
        'DocumentContent' => new \CurlFile($tx, 'text/html', sprintf("%d.html",$docr['ID']))
        ,'DocumentMetadata' => $ue);

        */

}

$fdid = (int)$frow['DVGID'];

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];
    
if (array_key_exists("edit",$_POST))
{
    QQ("DELETE FROM DVG WHERE DID = ? AND MID = ?",array($did,$mid));
    QQ("INSERT INTO DVG (DID,MID,DECISIONTYPE,UNIT,SIGNER) VALUES(?,?,?,?,?)",array($req['did'],$req['mid'],$req['type'],implode(",",$req['unit']),implode(",",$req['signer'])));
    redirect($whereret);
    die;
}

$dr = QQ("SELECT * FROM DVG WHERE DID = ? AND MID = ?",array($did,$mid))->fetchArray();
if (!$dr)
    $dr = array("DID" => $did,"MID" => $mid,"DECISIONTYPE" => '',"UNIT" => array(),"SIGNER" => array());

$typesurl = $basedvgurl.'/types';
$xtypes = json_decode(file_get_contents($typesurl));
$xtypes2 = $xtypes->decisionTypes;


$orgsurl = $basedvgurl.'/organizations/'.$fdid.'/details';
$o1 = json_decode(file_get_contents($orgsurl));
$o2 = $o1->units;
$o3 = $o1->signers;
//printdie($o3);

require_once "output.php";
?>

<div class="content" style="margin: 20px;">

<form method="POST" action="dvg.php">

<input type="hidden" name="edit" value="1" />
    <input type="hidden" name="did" value="<?= $did ?>" />
    <input type="hidden" name="mid" value="<?= $mid ?>" />
   
    Type:<br>
    <select name="type" class="select chosen-select is-fullwidth">
        <?php
        foreach($xtypes2 as $type)
        {
            if ($type->uid == $dr['DECISIONTYPE'])
            printf('<option value="%s" selected>%s</option>',$type->uid,$type->label);
            else
            printf('<option value="%s">%s</option>',$type->uid,$type->label);
        }
        ?>
</select><br><br>
Unit:<br>
<select name="unit[]" class="select chosen-select is-fullwidth" multiple>
        <?php
        $units = explode(",",$dr['UNIT']);
        foreach($o2 as $unit)
        {
            if (in_array($unit->uid,$units))
            printf('<option value="%s" selected>%s</option>',$unit->uid,$unit->label);
            else
            printf('<option value="%s">%s</option>',$unit->uid,$unit->label);
        }
        ?>
</select><br><br>
User:<br>
<select name="signer[]" class="select chosen-select is-fullwidth" multiple>
        <?php
        $signers = explode(",",$dr['SIGNER']);
        foreach($o3 as $unit)
        {
            if (in_array($unit->uid,$signers))
            printf('<option value="%s" selected>%s %s</option>',$unit->uid,$unit->firstName,$unit->lastName);
            else
            printf('<option value="%s">%s %s</option>',$unit->uid,$unit->firstName,$unit->lastName);
        }
        ?>
</select><br><br>
<button class="button is-success">Submit</button>
</form>
<script>
    chosen();
</script>