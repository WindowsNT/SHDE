<?php
require_once "functions.php";

if (!$u)
    diez();

require_once "output.php";

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];


if (array_key_exists("del",$req))
{
    $a = UserAccessComment($req['del'],$u->uid);
    if ($a == 2)
        QQ("DELETE FROM COMMENTS WHERE ID = ?",array(
        $req['del']
    ));
    redirect(sprintf("comments.php?did=%s&mid=%s",$req['did'],$req['mid']));
    die;
}

$doc = DRow($req['did'],1);
if (!$doc)
    diez();    
$acc = UserAccessDocument($req['did'],$u->uid);
if ($acc != 2)
    {
        redirect($whereret);
        die;
    }

if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
    {
        redirect(sprintf("decrypt.php?did=%s",$doc['ID']));
        die;
    }

if (!array_key_exists("mid",$req))
    $req['mid'] = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($req['did']))->fetchArray()[0];
$msg = MRow($req['mid'],1);
if (!$msg)
    diez();
 
if (array_key_exists("msg",$_POST))
{
    $dr = DRow($req['did']);
    if ($dr['CLASSIFIED'] > 0)
    {
        // Passwords
        $pwd = PasswordFromSession($dr['ID']);
        if ($pwd === FALSE)
            diez();
        $req['msg'] = ed($req['msg'],$pwd,'e');
    }
    QQ("INSERT INTO COMMENTS (UID,DID,MID,COMMENT,DATE) VALUES(?,?,?,?,?)",array(
        $u->uid,$req['did'],$req['mid'],$req['msg'],time()
    ));
    redirect($whereret);
    die;
}
function PrintComments()
{
    global $msg,$doc,$u;

    $s = '<table class="datatable table">';
    $s .= '<thead><th class="all">ID</th><th class="all">Από</th><th class="all">Εκδοση</th><th class="all">Ημερομηνία</th><th class="all">Σχόλιο</th><th class="all"></th></thead>';
    $s .= '<tbody>';
    $q = QQ("SELECT * FROM COMMENTS WHERE DID = ? ORDER BY DATE DESC",array($doc['ID']));
    while($r = $q->fetchArray())
    {
        DocumentDecrypt($r);
        $ur = UserRow($r['UID']);
        $a = UserAccessComment($r['ID'],$u->uid);
        if ($a == 0)
            continue;

        $s .= '<tr>';
        $s .= sprintf('<td>%s</td>',$r['ID']);
        $s .= sprintf('<td>%s %s</td>',$ur['LASTNAME'],$ur['FIRSTNAME']);
        $s .= sprintf("<td>%s</td>",date("d/m/Y H:i",$msg['DATE']));
        $s .= sprintf("<td>%s</td>",date("d/m/Y H:i",$r['DATE']));
        $s .= sprintf('<td>%s</td>',$r['COMMENT']);
        if ($a == 1)
            $s .= sprintf('<td></td>');
        else
        {
            $s .= sprintf('<td><a href="javascript:delcid(%s);">Διαγραφή</a></td>',$r['ID']);

        }
        $s .= '</tr>';
    }
    $s .= '</tbody></table>';
    echo $s;
}

PrintHeader($whereret);
PrintComments();

?>
<script>
    function delcid(id)
    {
        if (confirm("Σίγουρα;"))
            {
                var r = "comments.php?did=" + <?= $req['did'] ?> + "&mid=" + <?= $req['mid'] ?> + "&del=" + id;
                window.location = r;
            }
    }
</script>
<br><br>
Προσθήκη Σχολίου:<hr>
<form method="POST" action="comments.php">
<input type="hidden" name="did" value="<?= $req['did'] ?>">
<input type="hidden" name="mid" value="<?= $req['mid'] ?>">
<textarea name="msg" id="msg" data-lines="20"></textarea>
<br><br>
<button class="button is-primary">Υποβολή</button>
</form>
<?php
    printf('<button href="%s" class="autobutton button is-danger">Άκυρο</button>',$whereret);

?>