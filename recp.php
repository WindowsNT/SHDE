<?php
require_once "functions.php";

if (!$u)
    diez();

require_once "output.php";

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

$doc = null;
if (array_key_exists("mid",$req))
    {    
    $msg = MRow($req['mid'],1);
    if (!$msg)
        diez();
    $doc = DRow($msg['DID'],1);
    }
if (array_key_exists("did",$req))
    {    
    $doc = DRow($req['did'],1);
    }
    

if (!$doc)
    diez();
$er = EPRow($doc['EID']);
$fr = FRow($er['OID']);

if (UserAccessDocument($doc['ID'],$u->uid) != 2)
    {
        redirect($whereret);
        die;
    }

if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
    {
        redirect(sprintf("decrypt.php?did=%s",$doc['ID']));
        die;
    }

function SwitchIdToCode($n)
{
    if (!array_key_exists($n,$_POST))
        return;
    $a = array();
    foreach($_POST[$n] as $p)
    {
        $qq = QQ("SELECT * FROM ORGCHART WHERE ID = ?",array($p))->fetchArray();
        if ($qq)
            $a [] = $qq['CODE'];
    }
    $_POST[$n] = $a;
}

if (array_key_exists("did",$_POST))
{    
    if (array_key_exists("intr",$_POST) && count($_POST['intr']) == 1 && $_POST['intr'][0] == '')
        $_POST['intr'] = array();
    if (array_key_exists("koinx",$_POST) && count($_POST['koinx']) == 1 && $_POST['koinx'][0] == '')
        $_POST['koinx'] = array();
    if (array_key_exists("bccx",$_POST) && count($_POST['bccx']) == 1 && $_POST['bccx'][0] == '')
        $_POST['bccx'] = array();
    if (array_key_exists("eswx",$_POST) && count($_POST['eswx']) == 1 && $_POST['eswx'][0] == '')
        $_POST['eswx'] = array();

/* 
    // no need, picker already returns code
    SwitchIdToCode('intr');
    SwitchIdToCode('koinx');
    SwitchIdToCode('bccx');
    SwitchIdToCode('eswx');
*/
    $recpx = array_key_exists("intr",$_POST) ? serialize($_POST['intr']) : '';
    if (array_key_exists("intr",$_POST) && count($_POST['intr']) == 0)
        $recpx = '';
    $recpy = array_key_exists("ext",$_POST) ? serialize($_POST['ext']) : '';
    if (array_key_exists("ext",$_POST) && count($_POST['ext']) == 0)
        $recpy = '';
    $koinx = array_key_exists("koinx",$_POST) ? serialize($_POST['koinx']) : '';
    if (array_key_exists("koinx",$_POST) && count($_POST['koinx']) == 0)
        $koinx = '';
    $koiny = array_key_exists("koiny",$_POST) ? serialize($_POST['koiny']) : '';
    if (array_key_exists("koiny",$_POST) && count($_POST['koiny']) == 0)
        $koiny = '';
    $bccx = array_key_exists("bccx",$_POST) ? serialize($_POST['bccx']) : '';
    if (array_key_exists("bccx",$_POST) && count($_POST['bccx']) == 0)
        $bccx = '';
    $bccy = array_key_exists("bccy",$_POST) ? serialize($_POST['bccy']) : '';
    if (array_key_exists("bccy",$_POST) && count($_POST['bccy']) == 0)
        $bccy = '';
    $eswx = array_key_exists("eswx",$_POST) ? serialize($_POST['eswx']) : '';
    if (array_key_exists("eswx",$_POST) && count($_POST['eswx']) == 0)
        $eswx = '';

    if ($doc['CLASSIFIED'] > 0)
    {
        // Encrypt
        $pwd = PasswordFromSession($doc['ID']);
        if ($pwd === FALSE)
                die;
        $recpx = ed($recpx,$pwd,'e');
        $recpy = ed($recpy,$pwd,'e');
        $_POST['recpz'] = ed($_POST['recpz'],$pwd,'e');
        $koinx = ed($koinx,$pwd,'e');
        $koiny = ed($koiny,$pwd,'e');
        $_POST['koinz'] = ed($_POST['koinz'],$pwd,'e');
        $bccx = ed($bccx,$pwd,'e');
        $bccy = ed($bccy,$pwd,'e');
        $_POST['bccz'] = ed($_POST['bccz'],$pwd,'e');
        $eswx = ed($eswx,$pwd,'e');
    }

    QQ("UPDATE DOCUMENTS SET RECPX = ?,RECPY = ?,RECPZ = ?,KOINX = ?,KOINY = ?,KOINZ = ?,BCCX = ?,BCCY = ?,BCCZ = ?,ESWX = ? WHERE ID = ?",array($recpx,$recpy,$_POST['recpz'],$koinx,$koiny,$_POST['koinz'],$bccx,$bccy,$_POST['bccz'],$eswx,$_POST['did']));
    redirect($whereret);
    die;
}
require_once "output.php";
PrintHeader($whereret);
echo '<form action="recp.php" method="POST"> ';

printf('<input type="hidden" name="did" value="%s">',$req['did']);
if (array_key_existS("mid",$req))
    printf('<input type="hidden" name="mid" value="%s">',$req['mid']);

echo '<br><br><article class="panel is-primary">
<p class="panel-heading">
  Παραλήπτες
</p>
<div style="margin:20px">
';
echo '<br><br>Παραλήπτες εντός ΚΣΗΔΕ:<br><br>';
$restr = array();
if ($er['LIMITCODES'] && strlen($er['LIMITCODES']))
    $restr = explode(",",$er['LIMITCODES']);
if ($fr['LIMITCODES'] && strlen($fr['LIMITCODES']))
    $restr = explode(",",$fr['LIMITCODES']);

$s1 = EchoShdePicker(random_int(700000,799999),"intr[]",$doc['RECPX'] && strlen($doc['RECPX']) ? unserialize($doc['RECPX']) : array(),1,$restr);
echo $s1;
echo '<br><br>Παραλήπτες εκτός ΚΣΗΔΕ από το βιβλίο διευθύνσεων:<br><br>';
echo PickReceipientsAB($er['OID'],$doc['EID'],$u->uid,$doc['ID'],"ext[]",$doc['RECPY'] && strlen($doc['RECPY']) ? unserialize($doc['RECPY']) : array(),$doc['CLASSIFIED']);

echo '<br><br>Έξτρα Παραλήπτες (χωρισμένοι με κόμμα):<br><br>';
printf('<input type="text" class="input" name="recpz" value="%s">',$doc['RECPZ']);

echo '<br><br></div></article>';

echo '<br><br><article class="panel is-info">
<p class="panel-heading">
  Κοινοποιήσεις
</p>
<div style="margin:20px">
';

echo '<br><br>Κοινοποιήσεις εντός ΚΣΗΔΕ:<br><br>';
echo EchoShdePicker(random_int(300000,499999),"koinx[]",$doc['KOINX'] && strlen($doc['KOINX']) ? unserialize($doc['KOINX']) : array(),1,$restr);
//echo PickReceipientsKS("koinx[]",$doc['KOINX'] && strlen($doc['KOINX']) ? unserialize($doc['KOINX']) : array());

echo '<br><br>Κοινοποιήσεις εκτός ΚΣΗΔΕ από το βιβλίο διευθύνσεων:<br><br>';
echo PickReceipientsAB($er['OID'],$doc['EID'],$u->uid,$doc['ID'],"koiny[]",$doc['KOINY'] && strlen($doc['KOINY']) ? unserialize($doc['KOINY']) : array(),$doc['CLASSIFIED']);

echo '<br><br>Έξτρα Κοινοποιήσεις (χωρισμένοι με κόμμα):<br><br>';
printf('<input type="text" class="input" name="koinz" value="%s">',$doc['KOINZ']);

echo '<br><br></div></article>';

echo '<br><br><article class="panel is-secondary">
<p class="panel-heading">
  Κρυφές Κοινοποιήσεις
</p>
<div style="margin:20px">
';

//echo '<br><br>Κρυφές Κοινοποιήσεις εντός ΚΣΗΔΕ:<br><br>';
//echo PickReceipientsKS("bccx[]",strlen($doc['BCCX']) ? unserialize($doc['BCCX']) : array());

echo '<br><br>Κρυφές Κοινοποιήσεις από το βιβλίο διευθύνσεων:<br><br>';
echo PickReceipientsAB($er['OID'],$doc['EID'],$u->uid,$doc['ID'],"bccy[]",$doc['BCCY'] && strlen($doc['BCCY']) ? unserialize($doc['BCCY']) : array(),$doc['CLASSIFIED']);

echo '<br><br>Έξτρα Κρυφές Κοινοποιήσεις (χωρισμένοι με κόμμα):<br><br>';
printf('<input type="text" class="input" name="bccz" value="%s">',$doc['BCCZ']);

echo '<br><br></div></article>';

echo '<br><br><article class="panel is-info">
<p class="panel-heading">
  Εσωτερική Διανομή
</p>
<div style="margin:20px">
';

//echo PickReceipientsKS("eswx[]",$doc['ESWX'] && strlen($doc['ESWX']) ? unserialize($doc['ESWX']) : array());
echo EchoShdePicker(random_int(100000,299999),"eswx[]",$doc['ESWX'] && strlen($doc['ESWX']) ? unserialize($doc['ESWX']) : array(),0,$restr);

echo '<br><br></div></article>';

?>
    <br><br>
    <button class="button is-primary">Υποβολή</button>
    </form>
<?php
    printf('<button href="%s" class="autobutton button is-danger">Άκυρο</button>',$whereret);

