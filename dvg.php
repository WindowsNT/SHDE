<?php

require_once "functions.php";
if (!$u)
    diez();

if (!$u->superadmin)
    diez();

$did = $req['did'];
$mid = 0;
if (array_key_exists("mid",$req))
    $mid = $req['mid'];
$dr = DRow($did,1);
if (UserAccessDocument($did,$u->uid) != 2)
    diez();
$msg = MRow($mid,1);

$erow = EPRow($dr['EID']);
$frow = FRow($erow['OID']);
$dirow = QQ("SELECT * FROM DVG WHERE DID = ? AND MID = ?",array($did,$mid))->fetchArray();
$basedvgurl = 'https://diavgeia.gov.gr/opendata';
if ($frow['DVGID'] == '10599_api' && $frow['DVGPASS'] == 'User@10599')
    $basedvgurl = 'https://test3.diavgeia.gov.gr/luminapi/opendata';
$fdid = (int)$frow['DVGID'];

function PostDvg()
{
    global $did,$mid,$erow,$frow,$dr,$basedvgurl,$msg,$dirow,$fdid;
    $c = curl_init();
    $st = $basedvgurl.'/decisions';

    $pdfmessage = GetBinary("MESSAGES","SIGNEDPDF",$msg['ID']);
    $pwd = 0;
    if ($dr['CLASSIFIED'] > 0)
    {
        $pwd = PasswordFromSession($dr['ID']);
        if ($pwd === FALSE)
        {
            printf("Not decrypted.<br>");
            return;
        }
    }

    if ($dr['CLASSIFIED'] > 0)
        $pdfmessage = ed($pdfmessage,$pwd,'d');




    $test = 0;
    $method = 1;
    $pub = 1;

    if ($test)
        $st = 'http://sylviamichael.hopto.org:7010/decisions';


    $prot = unserialize($dr['PROT']);
    $sig0 = '';
    foreach(explode(",",$dirow['SIGNER']) as $s)
    {
        if (strlen($sig0))
            $sig0 .= ',';
        $sig0 .= sprintf('"%s"',$s);
    }
    $sig1 = '';
    foreach(explode(",",$dirow['UNIT']) as $s)
    {
        if (strlen($sig1))
            $sig1 .= ',';
        $sig1 .= sprintf('"%s"',$s);
    }


    $ee = '{
        "publish": "%s",
        "protocolNumber": "%s",
        "subject": "%s",
        "decisionTypeId": "%s",
        "issueDate": "%s",
        "organizationId": "%s",
        "extraFieldValues": {},
        "signerIds": [
            %s
        ],
        "unitIds": [
            %s
        ],
        "thematicCategoryIds": [
            "20"
        ]
        ';
    if ($pub)
    {
        $ee .= ',
        "decisionDocumentBase64": "';
        $ee .= base64_encode($pdfmessage);
        $ee .= "\"\r\n";
    }
    $ee .= "\r\n}";



    $datef = date("Y-m-d",$prot['t']).'T'.date("H:i:s.v",$prot['t']).'Z';
    $em = sprintf($ee,$pub ? "true" : "false",
    $prot['n'],$dr['TOPIC'],$dirow['DECISIONTYPE'],
        $datef,
        //$prot['t'],
        $fdid,$sig0,$sig1,
    
        );


  
    printr($em);
  

    $x = json_decode($em);


    curl_setopt($c, CURLOPT_USERPWD, $frow['DVGID']  . ":" . $frow['DVGPASS'] );      
    curl_setopt_array($c, array(
        CURLOPT_URL => $st,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true, 
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => array(
           $method == 0 ? "Content-Type: multipart/form-data" : "Content-Type: application/json",
        ),
    ));

    $fields = json_encode($x);
    $data = array(
            "metadata" =>  new \CurlStringFile($fields, '1.json','application/json'),
            "decisionFile" => $test == 1 ? new \CurlStringFile("Hello", '1.txt','text/plain') : new \CurlStringFile($pdfmessage, '1.pdf','application/pdf'),
        );

    if ($method == 1)
        curl_setopt($c, CURLOPT_POSTFIELDS, $fields);
    else
        curl_setopt($c, CURLOPT_POSTFIELDS, $data);
    $r = curl_exec($c);
    printr($r); 
    die;

}


$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

if (array_key_exists("send",$_POST))
{
    PostDvg();
    die;
}

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
            printf('<option value="%s" selected>%s [%s]</option>',$type->uid,$type->label,$type->uid);
            else
            printf('<option value="%s">%s [%s]</option>',$type->uid,$type->label,$type->uid);
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
            printf('<option value="%s" selected>%s  [%s]</option>',$unit->uid,$unit->label,$unit->uid);
            else
            printf('<option value="%s">%s  [%s]</option>',$unit->uid,$unit->label,$unit->uid);
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
            printf('<option value="%s" selected>%s %s [%s]</option>',$unit->uid,$unit->firstName,$unit->lastName,$unit->uid);
            else
            printf('<option value="%s">%s %s [%s]</option>',$unit->uid,$unit->firstName,$unit->lastName,$unit->uid);
        }
        ?>
</select><br><br>
<button class="button is-success">Submit</button>
</form>


<form method="POST" action="dvg.php">

<input type="hidden" name="send" value="1" />
    <input type="hidden" name="did" value="<?= $did ?>" />
    <input type="hidden" name="mid" value="<?= $mid ?>" />
   

<button class="button is-success">SEND</button>
</form>

<script>
    chosen();
</script>