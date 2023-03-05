<?php


$req = array_merge($_GET,$_POST);

function QQZ_SQLite($dbs,$q,$arr = array(),$stmtx = null)
{
    global $lastRowID;
    global $superadmin;

	$stmt = $stmtx;
    if (!$stmt)
        $stmt = $dbs->prepare($q);
    if (!$stmt)
        return null;
    $i = 1;
    foreach($arr as $a)
    {
        $stmt->bindValue($i,$a);
        $i++;
    }
    $a = $stmt->execute();
    $lastRowID = $dbs->lastInsertRowID();
    if ($a === FALSE)
        {
            die("Database busy, please try later.");
        }
    return $a;
}

if (!array_key_exists("manual",$req))
{
    require_once "configuration.php";
    $db = new SQLite3($dbxxpending);
    QQZ_SQLite($db,"CREATE TABLE IF NOT EXISTS PENDINGMAIL (ID INTEGER PRIMARY KEY,MESSAGE TEXT)");
    $message = stream_get_contents(STDIN);
    QQZ_SQLite($db,"INSERT INTO PENDINGMAIL (MESSAGE) VALUES (?)",array($message));
    $db->close();
    $db = null;
    chmod ($dbxxpending, 0777);
    die;
}

require_once "functions.php";
$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

if (!array_key_exists("txt",$req))
{
    if ($req['fid'] == 0)
        die("Επιλέξτε πρώτα φάκελο εισερχομένων");

    if (UserAccessFolder($req['fid'],$u->uid) != 2)
        die;

        require_once "output.php";
        PrintHeader('eggr.php');

    ?>

    <form method="POST" action="incomingmail.php">
    <input type="hidden" name="fid" value="<?= $req['fid'] ?>"/>
    <input type="hidden" name="oid" value="<?= $req['oid'] ?>"/>
        <input type="hidden" name="eid" value="<?= $req['eid'] ?>"/>
    <textarea name="txt" class="textarea" rows="10" required></textarea><br><br>

    <button class="button is-success">Υποβολή</button>
</form>
<?php
    die;
}
$txt = $req['txt'];



$er = EPRow($req['eid']);
$or = FRow($req['oid']);

require_once "vendor/autoload.php";
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderConsts;

// use an instance of MailMimeParser as a class dependency
$mailParser = new MailMimeParser();
$message = Message::from($txt, false);

$fromm = $message->getHeaderValue(HeaderConsts::FROM);
$fromn = $message->getHeader(HeaderConsts::FROM)->getPersonName(); 
$subject = $message->getHeaderValue(HeaderConsts::SUBJECT);
$tos = $message->getHeader(HeaderConsts::TO)->getAddresses();
$recpz = '';
foreach($tos as $to)   
{
    $ton = $to->getName();
    $tom = $to->getEmail();
    if (strlen($recpz))
        $recpz .= ',';
    $recpz .= sprintf("%s (%s)",$ton,$tom);
}

$plain = $message->getTextContent();
$html = $message->getHtmlContent();

if ($html == $null)
    $html = $plain;
if ($html == $null)
    die("No message");

$prot = '';
$recpx = array();

$fromz = sprintf("%s (%s)",$fromn,$fromm);

QQ("INSERT INTO DOCUMENTS (EID,TOPIC,FID,PROT,CLSID,RECPX,FROMZ,RECPZ,TYPE) VALUES (?,?,?,?,?,?,?,?,?)",array(
    $req['eid'],$subject,$req['fid'],$prot,guidv4(),serialize($recpx),$fromz,$recpz,1
));

$did = $lastRowID;

QQ("INSERT INTO MESSAGES (DID,MSG,DATE) VALUES (?,?,?)",array(
    $did,$html,time()
));

redirect($whereret);