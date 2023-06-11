<?php
require_once "functions.php";

if (!$u)
    diez();

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

$doc = DRow($req['did'],1);
if (!$doc)
    diez();
if (!array_key_exists("mid",$req))
{
    $req['mid'] = LastMsgID($doc['ID']);
}
$msg = MRow($req['mid'],1);
if (!$msg)
    diez();


if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
{
    redirect(sprintf("decrypt.php?did=%s",$doc['ID']));
    die;
}

if (UserAccessMessage($req['mid'],$u->uid) != 2)
    diez();

if (array_key_exists("delete",$req))
{
    $r = QQ("DELETE FROM ATTACHMENTS WHERE ID = ?",array($req['delete']))->fetchArray();
    QQ("VACUUM");
    redirect(sprintf("att.php?mid=%s&did=%s",$req['mid'],$req['did']));
    die;
}

if (array_key_exists("view",$req))
{
    $att = QQ("SELECT * FROM ATTACHMENTS WHERE ID = ?",array($req['view']))->fetchArray();

    $s = GetBinary('ATTACHMENTS','DATA',$att['ID']);
    if ($doc['CLASSIFIED'] > 0)
    {
        // Encrypt all
        $pwd = PasswordFromSession($doc['ID']);
        if ($pwd === FALSE)
            die;
        $att['TYPE'] = ed($att['TYPE'],$pwd,'d');
        $s = ed($s,$pwd,'d');
    }
    header("Content-Type: {$att['TYPE']}");
    if (array_key_exists("download",$req))
        header(sprintf("Content-Disposition: attachment; filename=\"%s\"",$att['NAME']));
    echo $s;
    die;
}

require_once "output.php";

if (array_key_exists("fileToUpload",$_FILES))
{
    $data = file_get_contents($_FILES['fileToUpload']['tmp_name']);
    $name = $_FILES['fileToUpload']['name'];
    $type = $_FILES['fileToUpload']['type'];
    $desc = $req['desc'];

    if ($doc['CLASSIFIED'] > 0)
    {
        // Encrypt all
        $pwd = PasswordFromSession($doc['ID']);
        if (!$pwd)
            {
                die;
            }
            $data = ed($data,$pwd,'e');
            $type = ed($type,$pwd,'e');
            $name = ed($name,$pwd,'e');
            $desc = ed($desc,$pwd,'e');
        }

    QQ("INSERT INTO ATTACHMENTS (MID,NAME,TYPE,DESCRIPTION,DATA) VALUES(?,?,?,?,?)",array($_POST['mid'],$name,$type,$desc,$data));
    redirect("att.php?did={$_POST['did']}&mid={$_POST['mid']}");
    die;
}

if (array_key_exists("new",$req))
{
    echo '<div class="content" style="margin: 20px;">';
    ?>
    <form  action="att.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="mid" value="<?= $msg['ID'] ?>" />
        <input type="hidden" name="did" value="<?= $doc['ID'] ?>" />
        Περιγραφή αρχείου:
        <input type="text" name="desc" class="input" required>
        <br><br>
        Επιλέξτε αρχείο:
        <input type="file" name="fileToUpload" id="fileToUpload" required>

        <br><br>
        <button class="autobutton button is-success">Υποβολή</button>
    </form>
    <button href="<?= $whereret ?>" class="autobutton button is-danger">Πίσω</button>
    <?php
}
else
    {
        $bp = sprintf('&nbsp; <button class="button autobutton is-primary block" href="att.php?mid=%s&did=%s&new=1">Προσθήκη</button> ',$msg['ID'],$doc['ID']);
        PrintHeader($whereret,$bp);
        $q1 = QQ("SELECT * FROM ATTACHMENTS WHERE MID = ?",array($msg['ID']));
        ?>
        <script>
            function del(id)
            {
                if (confirm('Σίγουρα;'))
                {
                    window.location = "att.php?mid=<?= $msg['ID'] ?>&did=<?= $doc['ID'] ?>&delete=" + id;
                }
            }
        </script>
        <table class="table datatable">
        <thead>
            <th class="all">ID</th>
            <th  class="all">Type</th>
            <th  class="all">Όνομα</th>
            <th  class="all">Περιγραφή</th>
            <th  class="all">Επιλογές</th>
        </thead>
        <tbody>
        <?php
        while($r1 = $q1->fetchArray())
        {
            printf('<tr>');

            if ($doc['CLASSIFIED'] > 0)
            {
                $pwd = PasswordFromSession($doc['ID']);
                if (!$pwd)
                    {
                        die;
                    }
                $r1['TYPE'] = ed($r1['TYPE'],$pwd,'d');
                $r1['NAME'] = ed($r1['NAME'],$pwd,'d');
                $r1['DESCRIPTION'] = ed($r1['DESCRIPTION'],$pwd,'d');
            }

            printf('<td>%s</td>',$r1['ID']);
            printf('<td>%s</td>',$r1['TYPE']);
            printf('<td>%s</td>',$r1['NAME']);
            printf('<td>%s</td>',$r1['DESCRIPTION']);
            printf('<td><a href="att.php?view=%s&mid=%s&did=%s" target="_blank">Προβολή</a> &mdash; <a href="att.php?view=%s&mid=%s&did=%s&download=1" target="_blank">Κατέβασμα</a> &mdash; <a href="javascript:del(%s);">Διαγραφή</a></td>',$r1['ID'],$msg['ID'],$doc['ID'],$r1['ID'],$msg['ID'],$doc['ID'],$r1['ID']);
            printf('</tr>');
        }
        ?>
        </tbody>
        </table>
        <?php
    } 
?>
