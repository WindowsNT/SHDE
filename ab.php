<?php

require_once "functions.php";
if (!$u)
    diez();
require_once "output.php";
PrintHeader('index.php','&nbsp; <button class="button is-primary autobutton block" href="ab.php?e=0">Νέα διεύθυνση</button> &nbsp; <button class="button is-link autobutton block" href="ab.php?vcf=1">VCF Import</button> &nbsp; <button class="button is-link autobutton block" href="ab.php?users=1">Users Import</button> <span></span>');

if (array_key_exists("delete",$req))
{
    $a = 0;
    $a = UserAccessAB($req['delete'],$u->uid);
    if ($a != 2)
        die;

    $r = QQ("DELETE FROM ADDRESSBOOK WHERE ID = ?",array($req['delete']))->fetchArray();
    redirect("ab.php");
    die;
}


if (array_key_exists("c",$_POST))
{
    $r = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($_POST['c']))->fetchArray();
    $a = 0;
    if ($r)    
        $a = UserAccessAB($r['ID'],$u->uid);
    else
        {
            $a = 2;
        }
    if ($a != 2)
        die;

    $ur = UserRow($u->uid);
    if ($ur['CLASSIFIED'] < $_POST['classification'])
        $_POST['classification'] = 0;

    if (!$r)
    {
        // Create
            QQ("INSERT INTO ADDRESSBOOK (LASTNAME,FIRSTNAME,EMAIL,CLASSIFIED,OID,EID,A1,A2,A3,TELS) VALUES (?,?,?,?,?,?,?,?,?,?)",array(
                $_POST['lastname'],$_POST['firstname'],$_POST['email'],$_POST['classification'],$_POST['oid'],$_POST['eid'],$_POST['a1'],$_POST['a2'],$_POST['a3'],$_POST['tels']
            ));
    }
    else
    {
        // Edit
        QQ("UPDATE ADDRESSBOOK SET LASTNAME = ?,FIRSTNAME = ?,EMAIL = ?,CLASSIFIED = ?,OID = ?,EID = ?,A1 = ?,A2 = ?,A3 = ?,TELS = ? WHERE ID = ?",array(
            $_POST['lastname'],$_POST['firstname'],$_POST['email'],$_POST['classification'],$_POST['oid'],$_POST['eid'],$_POST['a1'],$_POST['a2'],$_POST['a3'],$_POST['tels']

            ,$_POST['c']));
    }
    redirect("ab.php");
    die;
}

require_once "vendor/autoload.php";

use JeroenDesloovere\VCard\VCardParser;
if (array_key_exists("vcf",$req) && $req['vcf'] == 2)
{
    ini_set('display_errors', 0); error_reporting(E_ALL);
    $data = file_get_contents($_FILES['fileToUpload']['tmp_name']);
    $parser = new VCardParser($data);
    QQ("BEGIN TRANSACTION;");
    foreach($parser as $vcard) 
    {
        $tels = '';
        foreach($vcard->phone as $ph2)
        {
            foreach($ph2 as $ph3)
            {
                if (strlen($tels))
                    $tels .= ',';
                $tels .= $ph3;
                
            }
        }
        $mails = '';
        foreach($vcard->email as $ph2)
        {
            foreach($ph2 as $ph3)
            {
                if (strlen($mails))
                    $mails .= ',';
                $mails .= $ph3;
                
            }
        }
    QQ("INSERT INTO ADDRESSBOOK (LASTNAME,FIRSTNAME,EMAIL,CLASSIFIED,OID,EID,TELS) VALUES (?,?,?,?,?,?,?)",array(
        $vcard->lastname,$vcard->firstname,$mails,0,$_POST['oid'],$_POST['eid'],$tels
    ));
    }
    QQ("COMMIT");
    redirect("ab.php");
    die;
}


function CreateOrEditAB($e)
{
    global $u;
    $r = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($e))->fetchArray();

    // Has access ?
    $a = 0;
    if ($r)    
        $a = UserAccessEP($r['ID'],$u->uid);
    else
    if (count($u->fadmin) || count($u->epadmin))
        $a = 2;
    else
    if ($u->superadmin)
        $a = 2;

    if ($a != 2)
        return;

    if (!$r)
        $r = array("ID" => 0, "OID" => 0,"PARENT" => "","EID" => "", "SHDE" => "", "CLASSIFIED" => "","LASTNAME" => "","FIRSTNAME" => "", "EMAIL" => "","DATA" => ""
        , "A1" => "","A2" => "","A3" => "", "TELS" => "");

        ?>
        <br><br>
    <form method="POST" action="ab.php">
    <input type="hidden" name="c" value="<?= $r['ID'] ?>" />
    Όνομα: <br>
    <input type="text" class="input" name="firstname" value="<?= $r['FIRSTNAME']?>" required/>
    Επίθετο: <br>
    <input type="text" class="input" name="lastname" value="<?= $r['LASTNAME']?>" required/>
    Email: <br>
    <input type="email" class="input" name="email" value="<?= $r['EMAIL']?>" required/>
    Διεύθυνση: <br>
    <input type="text" class="input" name="a1" value="<?= $r['A1']?>" />
    TK: <br>
    <input type="text" class="input" name="a2" value="<?= $r['A2']?>" />
    Πόλη: <br>
    <input type="text" class="input" name="a3" value="<?= $r['A3']?>" />
    Τηλέφωνα: <br>
    <input type="text" class="input" name="tels" value="<?= $r['TELS']?>" />
    <br><br>
    <?php
            printf('Φορέας: %s<br><br>',PickOrganization("oid",array($r['OID']),0,$u->uid,0,$u->superadmin));
            printf('Endpoint: %s<br><br>', PickEP("eid",array($r['EID']),0,$u->uid,0,0,$u->superadmin,1));
        ?>


    Διαβάθμιση: <br>
        <?php
        echo PickClassification("classification",array($r['CLASSIFIED']));
        ?>
    <br><br>

    <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="ab.php" class="autobutton button is-danger">Άκυρο</button>

    <?php

}


function VCFImport()
{
    global $u;
    $a = 0;
    if (count($u->fadmin) || count($u->epadmin))
        $a = 2;
    else
    if ($u->superadmin)
        $a = 2;

    if ($a != 2)
        return;

        ?>
        <br><br>
    <form method="POST" action="ab.php" enctype="multipart/form-data">
    <input type="hidden" name="vcf" value="2" />
    <?php
            printf('Φορέας: %s<br><br>',PickOrganization("oid",array(),0,$u->uid,0,$u->superadmin));
            printf('Endpoint: %s<br><br>', PickEP("eid",array(),0,$u->uid,0,0,$u->superadmin,1));
        ?>

    Επιλέξτε αρχείο:
        <input type="file" name="fileToUpload" id="fileToUpload" accept=".vcf" required>

        <br><br>
        <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="ab.php" class="autobutton button is-danger">Άκυρο</button>

    <?php

    }

    
function UImport()
{
    global $u;
    $a = 0;
    if (count($u->fadmin) || count($u->epadmin))
        $a = 2;
    else
    if ($u->superadmin)
        $a = 2;

    if ($a != 2)
        return;

        ?>
        <br><br>
    <form method="POST" action="ab.php" enctype="multipart/form-data">
    <input type="hidden" name="users" value="2" />
    <?php
            printf('Φορέας: %s<br><br>',PickOrganization("oid",array(),0,$u->uid,0,$u->superadmin));
            printf('Endpoint: %s<br><br>', PickEP("eid",array(),0,$u->uid,0,0,$u->superadmin,1));
        ?>

        <br><br>
        <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="ab.php" class="autobutton button is-danger">Άκυρο</button>

    <?php

    }

function PrintAB()
{
    global $u;
    $q = QQ("SELECT * FROM ADDRESSBOOK ORDER BY OID ASC,EID ASC,LASTNAME ASC");
    ?>
    <script>
        function del(id)
        {
            if (confirm('Σίγουρα;'))
            {
                window.location = "ab.php?delete=" + id;
            }
        }
    </script>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th class="all">Επίθετο</th>
        <th class="all">Όνομα</th>
        <th class="all">Φορέας</th>
        <th class="all">Endpoint</th>
        <th class="all">e-mail</th>
        <th class="all">Διεύθυνση</th>
        <th class="all">Τηλέφωνα</th>
        <th class="all">Διαβάθμιση</th>
        <th  class="all">Επιλογές</th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        $a = UserAccessAB($r['ID'],$u->uid);
        if ($a == 0)
            continue;
        printf('<tr>');

        printf('<td>%s</td>',$r['ID']);
        printf('<td>%s</td>',$r['LASTNAME']);
        printf('<td>%s</td>',$r['FIRSTNAME']);

        $oidr = FRow($r['OID']);
        if ($oidr)
            printf('<td>%s</td>',$oidr['NAME']);
        else
            printf('<td></td>');
        $eidr = EPRow($r['EID']);
        if ($eidr)
            printf('<td>%s</td>',$eidr['NAME']);
        else
            printf('<td></td>');

        printf('<td>%s</td>',$r['EMAIL']);
        printf('<td>%s %s %s</td>',$r['A1'],$r['A2'],$r['A3']);
        printf('<td>%s</td>',$r['TELS']);
        printf('<td>%s</td>',ClassificationString($r['CLASSIFIED']));
        printf('<td>');
        if ($a == 2)
            printf('<a href="ab.php?e=%s">Επεξεργασία</a> ',$r['ID'],$r['ID']);
        if ($a == 2)
            printf(' &mdash; <a href="javascript:del(%s);">Διαγραφή</a>',$r['ID']);
        printf('</td>');


        printf('</tr>');
    }
    ?>
    </tbody>
    </table>
    <?php
}
if (array_key_exists("users",$req) && $req['users'] == 2)
{
    QQ("BEGIN TRANSACTION");
    $q = QQ("SELECT * FROM USERS WHERE EMAIL IS NOT NULL");
    while($r = $q->fetchArray())
    {
        $e = QQ("SELECT * FROM ADDRESSBOOK WHERE EMAIL = ?",array($r['EMAIL']))->fetchArray();
        if ($e)
            continue;

        QQ("INSERT INTO ADDRESSBOOK (LASTNAME,FIRSTNAME,EMAIL,OID,EID) VALUES (?,?,?,?,?)",array(
                $r['LASTNAME'],$r['FIRSTNAME'],$r['EMAIL'],$req['oid'],$req['eid']
        ));
    }
    QQ("COMMIT");
    redirect("ab.php");

}
else
if (array_key_exists("users",$req) && $req['users'] == 1)
{
    UImport();
}
else
if (array_key_exists("vcf",$req) && $req['vcf'] == 1)
{
    VCFImport();
}
else
if (array_key_exists("e",$req))
{
    CreateOrEditAB($req['e']);
}
else
    {
    PrintAB();
    }
?>



<?php    
