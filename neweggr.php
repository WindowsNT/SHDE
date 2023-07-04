<?php 

require_once "functions.php";
if (!$u)
    diez();

require_once "output.php";

$doc = null;
$msg = null;

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];
    PrintHeader($whereret);

if (array_key_exists("deletemid",$req))
{
    DeleteMessage($req['deletemid']);
    redirect($whereret);
    die;
}
if (array_key_exists("deletedid",$req))
{
    DeleteDocument($req['deletedid']);
    redirect($whereret);
    die;
}


require_once "not.php";
NOT_Scripts();

if (array_key_exists("c",$_POST))
{
    // replace 
    $_POST['msg'] = str_replace('<span style="font-size: 1rem; font-weight: 400;">','<span>',$_POST['msg']);
    $req['msg'] = $_POST['msg'];
    $ur = UserRow($u->uid);
    $_POST['formatting'] = serialize(array("form_recp" => $_POST['form_recp']));
    if (!array_key_exists("classification",$_POST))
        $_POST['classification']  = 0;
    if ($ur['CLASSIFIED'] < $_POST['classification'])
        $_POST['classification'] = 0;

    if (!array_key_exists("addedsigners",$_POST))
        $_POST['addedsigners'] = array();

    // Passwords
    if ($_POST['classification'] > 0)
    {
        if ($_POST['pwd1'] != $_POST['pwd2'])
            die("Passwords do not match.");
        if ($_POST['pwd1'] == "" || $_POST['pwd2'] == "" )
            die("Empty password is not allowed in classified documents.");


        $_POST['topic'] = ed($_POST['topic'],$_POST['pwd1'],'e');
        $_POST['msg'] = ed($_POST['msg'],$_POST['pwd1'],'e');
        $_POST['info'] = ed($_POST['info'],$_POST['pwd1'],'e');
    }
    if (!array_key_exists('pdf2',$_POST) && !array_key_exists('pdf1',$_POST))
        {
            $_POST['pdf1'] = '';
            $_POST['pdf2'] = '';
        }
    if (($_POST['pdf1'] || $_POST['pdf2']) && $_POST['pdf1'] != $_POST['pdf2'])
        die("PDF Passwords do not match.");

    $notified = array();
    if (array_key_exists("did",$_POST) && $_POST['did'] > 0)
    {
        if (UserAccessDocument($_POST['did'],$u->uid) != 2)
            {
                redirect($whereret);
                die;
            }
        $dr= DRow($_POST['did'],1);
        if ($dr['UID'] != $u->uid)
            $notified[] = $dr['UID'];
        QQ("UPDATE DOCUMENTS SET ENTRYCREATED = ?,TOPIC = ?,UID = ?,FID = ?,CLASSIFIED = ?,PRIORITY = ?,TYPE = ?,CATEGORY = ?,ORIGINALITY = ?,FORMATTING =?,DUEDATE = ?,COLOR = ?,ADDEDSIGNERS = ?,PDFPASSWORD = ?,SIGNERTITLES = ?,ORIGINALITYEXTRA = ? WHERE ID = ?",array(time(),$_POST['topic'],$u->uid,$_POST['parent'],$_POST['classification'],$_POST['priority'],$_POST['type'],$_POST['category'],$_POST['originality'],$_POST['formatting'],strtotime($_POST['due']),$_POST['color'],implode(",",$_POST['addedsigners']),$_POST['pdf1'],$_POST['signertitles'],$_POST['originalityextra'],$_POST['did']));
        $fid = $_POST['parent'];
    }
    else
        {
            $fid = QQ("SELECT * FROM FOLDERS WHERE SPECIALID = ? AND EID = ?",array(FOLDER_OUTBOX,$_POST['eid']))->fetchArray()['ID'];
            $clsid = guidv4();
            QQ("INSERT INTO DOCUMENTS (ENTRYCREATED,UID,EID,TOPIC,FID,CLASSIFIED,PRIORITY,TYPE,CATEGORY,ORIGINALITY,FORMATTING,DUEDATE,CLSID,COLOR,ADDEDSIGNERS,PDFPASSWORD,SIGNERTITLES,ORIGINALITYEXTRA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",array(
                time(),
                $u->uid,
                $_POST['eid'],
                $_POST['topic'],
                $fid,
                $_POST['classification'],
                $_POST['priority'],
                $_POST['type'],
                $_POST['category'],
                $_POST['originality'],
                $_POST['formatting'],
                $_POST['due'] ? strtotime($_POST['due']) : '',
                $clsid,
                $_POST['color'],
                implode(",",$_POST['addedsigners']),
                $_POST['pdf1'],
                $_POST['signertitles'],
                $_POST['originalityextra']
            ));
            $_POST['did'] = $lastRowID;

            if ($_POST['reply'] > 0)
            {
                // create related document 
                QQ("UPDATE DOCUMENTS SET RELATED = ? WHERE ID = ?",array(-(int)$_POST['reply'],$_POST['did']));                

                // Receipients
                if ($_POST['ks'] > 0)
                {
                    QQ("UPDATE DOCUMENTS SET RECPX = ? WHERE ID = ?",array(serialize(array($_POST['ks'])),$_POST['did']));                
                }
                if ($_POST['ml'] != '')
                {
                    QQ("UPDATE DOCUMENTS SET RECPZ = ? WHERE ID = ?",array($_POST['ml'] ,$_POST['did']));                
                }
            }
        }

    if (array_key_exists("mid",$_POST) && $_POST['mid'] > 0)
        QQ("UPDATE MESSAGES SET MSG = ?,DATE = ?,UID = ?,INFO = ? WHERE ID = ?",array($_POST['msg'],time(),$u->uid,$_POST['info'],$_POST['mid']));
    else
        {
            QQ("INSERT INTO MESSAGES (UID,DID,MSG,DATE,INFO) VALUES (?,?,?,?,?)",array($u->uid,$_POST['did'],$_POST['msg'],time(),$_POST['info']));
            $_POST['mid'] = $lastRowID;
        }

    if ($_POST['classification'] > 0)
        $_SESSION[sprintf('shde_pwd_%s',$_POST['did'])] = $_POST['pwd1'] ;

    // Also final signer of this EID and epadmin/oid admin
    if (1)
    {
        $q11 = QQ("SELECT * FROM ROLES WHERE (ROLEID = ? OR ROLEID = ?) AND EID = ?",array(ROLE_SIGNER0,ROLE_EPADMIN,$_POST['eid']));
        while($r11 = $q11->fetchArray())
            $notified [] = $r11['ID'];
        $eidr = EPRow($_POST['eid']);
        $q11 = QQ("SELECT * FROM ROLES WHERE ROLEID = ? AND EID = 0 AND OID = ?",array(ROLE_FADMIN,$eidr['OID']));
        while($r11 = $q11->fetchArray())
            $notified [] = $r11['ID'];
        }

    PushMany($notified,sprintf("Επεξεργασία εγγράφου [%s].",$_POST['topic']));         
    $whereret = sprintf("eggr.php?oid=%s&eid=%s&fid=%s",$_POST['oid'],$_POST['eid'],$fid);   

    redirect($whereret);
    die;
}

$readonly = 0;

if (array_key_exists("did",$req))
    {
        $doc = DRow($req['did'],1);
        if (!$doc['FORMATTING'])
            $doc['FORMATTING'] = $defform;
    }
else
    $doc = array("ID" => "","TOPIC" => "","EID" => "","CLASSIFIED" => "","TYPE" => "0","COLOR" => "#000000","ORIGINALITY" => 0,"FORMATTING" => $defform,"CATEGORY" => "5","PRIORITY" => "0", "DUEDATE" => 0, "ADDEDSIGNERS" => "", "PDFPASSWORD" => "", "SIGNERTITLES" => "","ORIGINALITYEXTRA" => "");

if (array_key_exists("mid",$req))
    {
        $msg = MRow($req['mid'],1);
        if (QQ("SELECT * FROM MESSAGES WHERE DID = ? AND DATE > ?",array($msg['DID'],$msg['DATE']))->fetchArray())
            $readonly = 1;
    }
else
   {
   $msg = array("ID" => "","DATE" => "","MSG" => "","INFO" => "");
   if (array_key_exists("copy",$req))
   {
        $msg2 = MRow($req['copy'],1);
        if ($msg2)
            {
                $msg['MSG'] = $msg2['MSG'];
                $msg['INFO'] = $msg2['INFO'];
            }
   }
}

$reply = 0;
$ks = 0;
$ml = 0;
$replydoc = null;
if (array_key_exists("reply",$req))
    {
        $reply = $req['reply'];
        $replydoc = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($reply))->fetchArray();
    }
if (array_key_exists("ks",$req))
    $ks = $req['ks'];
if (array_key_exists("ml",$req))
    $ml = $req['ml'];

if ($readonly)
{
    printf("Θέμα: %s<hr>",$doc['TOPIC']);
    printf("%s<hr>",$msg['MSG']);
    printf('<button href="%s" class="autobutton button is-danger">Πίσω</button>',$whereret);
}
else
{
    $ur = UserRow($u->uid);
    if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
    {
        redirect(sprintf("decrypt.php?did=%s",$doc['ID']));
        die;
    }

?>

<form method="POST" action="neweggr.php">
    <input type="hidden" name="c"  />
    <input type="hidden" name="mid" value="<?= $msg['ID'] ?>"  />
    <input type="hidden" name="did" value="<?= $doc['ID'] ?>"  />
    <input type="hidden" name="reply" value="<?= $reply ?>"  />
    <input type="hidden" name="ml" value="<?= $ml ?>"  />
    <input type="hidden" name="ks" value="<?= $ks ?>"  />

        <article class="panel is-link">
  <p class="panel-heading">
    Αποστολέας
  </p>
  <div class="panel-block is-active">

    <div class="columns">
    <?php
        echo '<div class="column">';
        printf('Φορέας:<br> %s<br><br>',PickOrganization("oid",array(),0,$u->uid,0,0));
        $epsel = array();
        if ($replydoc)
        {
            $epsel[] = $replydoc['EID'];
        }
        echo '</div><div class="column">';
        printf('Endpoint:<br> %s<br>', PickEP("eid",$epsel,0,$u->uid,0,0,0,1));
        echo '</div>';
    ?>
    </div>
</div>
</article>
    
<article class="panel is-link">
  <p class="panel-heading">
    Βασικές Παράμετροι
  </p>
  <div class="panel-block is-active">
    <div class="columns">

    <?php
    if (array_key_exists("did",$req))
    {
        ?>
    <div class="column">    
        Φάκελος: <br>

        <?php
        echo PickFolder($doc['EID'],"parent",array($doc['FID']),$u->uid);
        ?>
        </div>
<br>
<?php
}
    ?>

    <div class="column" >    
    Θέμα: <br>
    <?php
        if ($replydoc)
            $doc['TOPIC'] = sprintf("Απ.: %s",$replydoc['TOPIC']);
    ?>
    <input type="text" class="input" name="topic" value="<?= $doc['TOPIC']?>" required/>
    </div>
    <div class="column">    
    Προτεραιότητα: <br>
    <?= PickPriority("priority",array($doc['PRIORITY'])); ?>
    </div>

    <?php
    if ($ur['CLASSIFIED'] > 0)
    {
        echo '<div class="column">';
        echo 'Διαβάθμιση: <br>';
        echo PickClassification("classification",array($doc['CLASSIFIED']),"pwd",$doc);
        echo '</div>';
    }
    ?>
    <div class="column">    
    Προστασία PDF με κωδικό (Θα αποσταλεί ως κρυπτογραφημένο ZIP): <br>
    <input class="input" type="password" name="pdf1" value="<?= $doc['PDFPASSWORD'] ?>" autocomplete="one-time-code"/>
    <br>
    Ξανά: <br>
    <input class="input" type="password" name="pdf2" value="<?= $doc['PDFPASSWORD'] ?>" autocomplete="one-time-code"/>
    </div>

    <br><br>

    <div class="column">
    Διεκπεραίωση μέχρι:
    <input type="date" name="due"  class="input" value="<?= ($doc['DUEDATE']  && strlen($doc['DUEDATE']) > 5) ? date("Y-m-d",(int)$doc['DUEDATE']) : "" ; ?>">
    </div>
    </div>
    </div>
</article>
    <br>

    <article class="panel is-link">
  <p class="panel-heading">
    Παράμετροι
  </p>
  <div class="panel-block is-active">
    <div class="columns">
    <div class="column">
    Τύπος:
        <select name="type" class="input chosen-select">
            <option value="0" <?= $doc['TYPE'] == 0 ? "selected" : "" ?>>Εγγραφο</option>
            <option value="1" <?= $doc['TYPE'] == 1 ? "selected" : "" ?>>Απλό e-mail</option>
            <option value="2" <?= $doc['TYPE'] == 2 ? "selected" : "" ?>>Αποστολή του πρώτου Attachment σαν περιεχόμενο</option>
        </select>
    </div>
    <div class="column">
    Κατηγορία:
        <select name="category" class="input chosen-select">
            <option value="1" <?= $doc['CATEGORY'] == 1 ? "selected" : "" ?>>Απόφαση</option>
            <option value="2" <?= $doc['CATEGORY'] == 2 ? "selected" : "" ?>>Σύμβαση</option>
            <option value="3" <?= $doc['CATEGORY'] == 3 ? "selected" : "" ?>>Λογαριασμός</option>
            <option value="4" <?= $doc['CATEGORY'] == 4 ? "selected" : "" ?>>Ανακοίνωση</option>
            <option value="5" <?= $doc['CATEGORY'] == 5 ? "selected" : "" ?>>Άλλο</option>
        </select>
        <br>
        <br>
    Πρωτοτυπία:
        <select name="originality" class="input chosen-select">
            <option value="0" <?= $doc['ORIGINALITY'] == 0 ? "selected" : "" ?>>Αρχικό</option>
            <option value="1" <?= $doc['ORIGINALITY'] == 1 ? "selected" : "" ?>>Ακριβές Αντίγραφο</option>
        </select>
        <br>
    </div>
    <div class="column">
    Πληροφορίες: <br>
    <input type="text" class="input" name="info" value="<?= $msg['INFO']?>" />
    
    <br>
    Μορφή Παραληπτών:
        <select name="form_recp" class="input chosen-select">
            <option value="0" <?= unserialize($doc['FORMATTING'])['form_recp'] == 0 ? "selected" : "" ?>>Πάνω</option>
            <option value="1" <?= unserialize($doc['FORMATTING'])['form_recp'] == 1 ? "selected" : "" ?>>Πίνακας Αποδεκτών</option>
        </select>
        <br>
    
    </div>
    <div class="column">
    Χρώμα:<br>
    <input type="color" id="color" name="color" value="<?= $doc['COLOR'] ?>"><br>
    </div>
    <div class="column">
    <?php
            printf('Επιπλέον υπογράφοντες:<br> %s<br>', PickUser("addedsigners[]",$doc['ADDEDSIGNERS'] ? explode(",",$doc['ADDEDSIGNERS']) : array(),1,array(),0,$u->uid));
    
    ?>
</div>
    <div class="column is-one-third">
    <?php
    printf('Τίτλοι υπογραφόντων:<br><input name="signertitles" type="text" class="input" id="signertitles" value="%s"><br>',$doc['SIGNERTITLES'] ? $doc['SIGNERTITLES']  : '' );
    ?>
    <?php
    printf('Επιπλέον πληροφορίες για Ακριβές Αντίγραφο:<br><input name="originalityextra" type="text" class="input" id="originalityextra" value="%s"><br>',$doc['ORIGINALITYEXTRA'] ? $doc['ORIGINALITYEXTRA']  : '' );
    ?>

</div>
    </div>
</div></article>

    <br>
    <article class="panel is-link">
  <p class="panel-heading">
  Κείμενο
  </p>
  <div class="panel-block is-active">
    : <br>
    <textarea name="msg" id="msg" data-lines="100"><?= $msg['MSG']?></textarea>
</div></article>
<br><br>
<button class="button is-primary">Υποβολή</button>
</form>
<pre id="PreSave"></pre>
<script>
    $(document).ready(function()
    {
        if (typeof(Storage) !== "undefined") 
            {
                var eee = "msg" + <?= array_key_exists("mid",$req) ? $req['mid']  : '0' ?>;
                var t = localStorage.getItem(eee);
                $("#PreSave").html(t);
            }        
        setInterval(() => {
            if (typeof(Storage) !== "undefined") 
            {
                var eee = "msg" + <?= array_key_exists("mid",$req) ? $req['mid']  : '0' ?>;
                var t = $('#msg').val();
                localStorage.setItem(eee, t);
            }

        }, 15000);
    });
</script>
<?php

    if (array_key_exists("did",$req) && array_key_exists("mid",$req))
        printf('<button href="%s" class="autobutton button is-danger">Άκυρο</button>',$whereret);
     else
        printf('<button href="index.php" class="autobutton button is-danger">Άκυρο</button>');
}
?>


    