<?php

require_once "functions.php";
if (!$u)
    diez();

if (!$u->superadmin)
    diez();

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

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
$durl = 'https://diavgeia.gov.gr';
$basedvgurl = 'https://diavgeia.gov.gr/opendata';
if ($frow['DVGID'] == '10599_api' && $frow['DVGPASS'] == 'User@10599')
    {
        $basedvgurl = 'https://test3.diavgeia.gov.gr/luminapi/opendata';
        $durl = 'https:///test3.diavgeia.gov.gr';
    }
$fdid = (int)$frow['DVGID'];

function PostDvg()
{
    global $did,$mid,$erow,$frow,$dr,$basedvgurl,$msg,$dirow,$fdid,$whereret;
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


    $jsin = json_decode($dirow['JSIN']);


    $test = 0;
    $method = 1;
    $pub = 1;

    if ($test)
        $st = 'http://sylviamichael.hopto.org:7010/decisions';


    $prot = unserialize($dr['PROT']);
    $sig0 = '';
    foreach($jsin->signerIds as $s)
    {
        if (strlen($sig0))
            $sig0 .= ',';
        $sig0 .= sprintf('"%s"',$s);
    }
    $sig1 = '';
    foreach($jsin->unitIds as $s)
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
    $prot['n'],$dr['TOPIC'],$jsin->decisionTypeId,
        $datef,
        //$prot['t'],
        $fdid,$sig0,$sig1,    
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
    $jr = json_decode($r);
    unset($x->decisionDocumentBase64);
//    printr($x);
    if (isset($jr->errors))
    {
        printr($jr); 

    }
    else
    {
        QQ("UPDATE DVG SET JSOUT = ? WHERE DID = ? AND MID = ?",array(json_encode($jr),$did,$mid));
        printf('Επιτυχής αποστολή στη διαύγεια. <a href="%s">Πίσω</a>',$whereret);
//        printr($jr); 

    }
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
    $j = new stdClass;

    $j->signerIds = $req['signer'];
    $j->unitIds = $req['unit'];
    $j->decisionTypeId = $req['type'];

    QQ("INSERT INTO DVG (DID,MID,JSIN) VALUES(?,?,?)",array($req['did'],$req['mid'],json_encode($j)));
    redirect($whereret);
    die;
}

$dr = QQ("SELECT * FROM DVG WHERE DID = ? AND MID = ?",array($did,$mid))->fetchArray();
if (!$dr)
    $dr = array("DID" => $did,"MID" => $mid,"JSIN" => '{}',"UNIT" => array(),"SIGNER" => array());

$typesurl = $basedvgurl.'/types';
$xtypes = json_decode(file_get_contents($typesurl));
$xtypes2 = $xtypes->decisionTypes;


$orgsurl = $basedvgurl.'/organizations/'.$fdid.'/details';
$o1 = json_decode(file_get_contents($orgsurl));
$o2 = $o1->units;
$o3 = $o1->signers;
//printdie($o3);

$jsin = json_decode($dr['JSIN'],true);
$jsout = json_decode($dr['JSOUT']);

if (strlen($dr['JSOUT']))
{
    if (array_key_exists("revoke",$req))
    {
        $c = curl_init();
        $st = $basedvgurl.'/decisions/requests/revocations';    
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
               "Content-Type: application/json",
            ),
        ));
    
        $ee = sprintf('{
            "ada": "%s",
            "comment": "None",
            "revocationReasonTagUid": "FAULTY_PDF",
            "secondLevelTagUid": "%s",
            "oldAda": " "
            }
            ',$req['revoke'],$jsin['decisionTypeId']);
        $x = json_decode($ee);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($x));
        $r = curl_exec($c);
        $jr = json_decode($r);
        printr($r);
    
        die("REVOKED");
    }
    require_once "output.php";

    printf('<div class="content" style="margin: 20px;">Έχει ανέβει στη δι@ύγεια!<hr>');
    printf('ADA: <b>%s</b><br>',$jsout->ada);
    printf('Ημερομηνία: <b>%s</b><br>',date("n/m/Y H:i",$jsout->submissionTimestamp));
    $uu = sprintf("%s/decision/view/%s",$durl,$jsout->ada);
    printf('URL: <a href="%s" target="_blank">%s</a><br>',$uu,$uu);
    printf('<a href="dvg.php?did=%s&mid=%s&revoke=%s">Revoke</a><br>',$did,$mid,$jsout->ada);
    printr($jsout);
    die;
}

require_once "output.php";
?>

<div class="content" style="margin: 20px;">

Παράμετροι για αποστολή στη Δι@ύγεια:<hr>
<form method="POST" action="dvg.php">


<input type="hidden" name="edit" value="1" />
    <input type="hidden" name="did" value="<?= $did ?>" />
    <input type="hidden" name="mid" value="<?= $mid ?>" />
   
    Type:<br>
    <select name="type" class="select chosen-select is-fullwidth">
        <?php
        foreach($xtypes2 as $type)
        {
            if (isset($jsin['decisionTypeId']) && $type->uid == $jsin['decisionTypeId'])
                printf('<option value="%s" selected>%s [%s]</option>',$type->uid,$type->label,$type->uid);
            else
                printf('<option value="%s">%s [%s]</option>',$type->uid,$type->label,$type->uid);
        }
        ?>
</select><br><br>
Unit:<br>
<select name="unit[]" class="select chosen-select is-fullwidth" multiple>
        <?php
        foreach($o2 as $unit)
        {
            if (isset($jsin['unitIds']) && in_array($unit->uid,$jsin['unitIds']))
                printf('<option value="%s" selected>%s  [%s]</option>',$unit->uid,$unit->label,$unit->uid);
            else
                printf('<option value="%s">%s  [%s]</option>',$unit->uid,$unit->label,$unit->uid);
        }
        ?>
</select><br>
User:<br>
<select name="signer[]" class="select chosen-select is-fullwidth" multiple>
        <?php
        foreach($o3 as $unit)
        {
            if (isset($jsin['signerIds']) && in_array($unit->uid,$jsin['signerIds']))
                printf('<option value="%s" selected>%s %s [%s]</option>',$unit->uid,$unit->firstName,$unit->lastName,$unit->uid);
            else
                printf('<option value="%s">%s %s [%s]</option>',$unit->uid,$unit->firstName,$unit->lastName,$unit->uid);
        }
        ?>
</select><br><br>
<button class="button is-success">Submit</button>
</form>

<?php
if (strlen($dr['JSIN']) > 5)
{
    ?>

Αποστολή στη Δι@ύγεια<hr>
<form method="POST" action="dvg.php">

<input type="hidden" name="send" value="1" />
    <input type="hidden" name="did" value="<?= $did ?>" />
    <input type="hidden" name="mid" value="<?= $mid ?>" />
   

<button class="button is-success">SEND</button>
</form>
<?php
}
?>
<script>
    chosen();
</script>