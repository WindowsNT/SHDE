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

    $test = 0;

    if ($test)
        $st = 'http://sylviamichael.hopto.org:7010/';

/*"extraFieldValues": {
    "cpv": [
        "42661000-7"
    ],
    "contestProgressType": "Πρόχειρος",
    "manifestSelectionCriterion": "Χαμηλότερη Τιμή",
    "manifestContractType": "Έργα",
    "orgBudgetCode": "Τακτικός Προϋπολογισμός",
    "estimatedAmount": {
        "amount": 500,
        "currency": "EUR"
    }
            "thematicCategoryIds": [
            "20"
        ],
        "decisionDocumentBase64": "[BASE-64 ENCODED FILE]",
        "attachments": {
            "attach": [
                {
                    "description": "Συνοδευτικό έγγραφο",
                    "filename": "attachment1.pdf",
                    "mimeType": "application/pdf",
                    "contentBase64": "[BASE-64 ENCODED FILE]"
                }
            ]
        }

},
*/
    $ee = '{
        "protocolNumber": "%s",
        "subject": "%s",
        "decisionTypeId": "%s",
        "issueDate": "%s",
        "organizationId": "%s",
        "signerIds": [
            %s
        ],
        "unitIds": [
            %s
        ]
    }';

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

    $em = sprintf($ee,
    $prot['n'],$dr['TOPIC'],$dirow['DECISIONTYPE'],date("Y-m-d",$prot['t']).'T'.date("H:i:s.v",$prot['t']).'Z',$fdid,$sig0,$sig1
        );
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
            "cache-control: no-cache",
            "Content-Type: multipart/form-data",
        ),
    ));

    $fields = json_encode($x);

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

    $data = array(
            "metadata" =>  new \CurlStringFile($fields, '1.json','application/json'),
            "decisionFile" => $test ? new \CurlStringFile("Hello", '1.txt','text/plain') : new \CurlStringFile($pdfmessage, '1.pdf','application/pdf'),
        );

    curl_setopt($c, CURLOPT_POSTFIELDS, $data);

    $r = curl_exec($c);
    printdie($r);

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


<form method="POST" action="dvg.php">

<input type="hidden" name="send" value="1" />
    <input type="hidden" name="did" value="<?= $did ?>" />
    <input type="hidden" name="mid" value="<?= $mid ?>" />
   

<button class="button is-success">SEND</button>
</form>

<script>
    chosen();
</script>