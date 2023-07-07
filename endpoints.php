<?php

require_once "functions.php";
if (!$u)
    diez();

    
    if (array_key_exists("delete",$req))
    {
        $a = UserAccessEP($req['delete'],$u->uid);
        if ($a != 2)
            die;
        if (array_key_exists("force",$req))
        {
            QQ("BEGIN TRANSACTION");
            DeleteWholeEndpoint($req['delete']);
            QQ("COMMIT");
            QQ("VACUUM");
            redirect("endpoints.php");
            die;
        }

        require_once "output.php";
        echo '<div class="content" style="margin:20px;">';
        echo 'Διαγραφή Endpoint';
    
        $fcount = CountDB("FOLDERS WHERE EID = ?",array($req['delete']));
        $docccount = CountDB("DOCUMENTS WHERE EID = ?",array($req['delete']));
        $abcount = CountDB("ADDRESSBOOK WHERE EID = ?",array($req['delete']));
        $rcount = CountDB("ROLES WHERE EID = ?",array($req['delete']));

        if ($fcount || $docccount || $abcount || $rcount) 
            printf("<br><b>Η διαγραφή του endpoint θα διαγράψει οριστικά και %s φακέλους με συνολικά %s έγγραφα που βρίσκονται σε αυτό, καθώς και %s εγγραφές στο βιβλίο διευθύνσεων και %s ρόλους προσωπικού!</b>",$fcount,$docccount,$abcount,$rcount);
    
        printf('<br><br><button href="endpoints.php?delete=%s&force=1" class="button autobutton is-danger">Επιβεβαίωση Διαγραφής</button><br>',$req['delete']);
        printf('<br><hr><button href="endpoints.php" class="button autobutton is-success">Πίσω</button>');
        die;
    }


if (array_key_exists("c",$_POST))
{
    $r = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($_POST['c']))->fetchArray();
    $a = 0;
    if ($r)    
        $a = UserAccessEP($r['ID'],$u->uid);
    else
        {
            $a2 = UserAccessOID($_POST['oid'],$u->uid);
            if ($a2 == 1 || $a2 == 2)
                $a = 2;
        }
    if ($a != 2)
        die;


    if ($_POST['pid'])
    {
        $parep = EPRow($_POST['pid']);
        if (!$parep)
            die;
        if ($parep['OID'] != $_POST['oid'])
            die("Wrong Parent OID");
    }

    if (!$r)
    {
        // Create
            QQ("INSERT INTO ENDPOINTS (NAME,NAMEEN,OID,PARENT,EMAIL,ALIASEMAIL,FORWARDEMAIL,INACTIVE,T0,T1,T2,T3,T4,T5,A1,A2,A3,TEL1,TEL2,TEL3) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",array($_POST['name'],$_POST['nameen'],$_POST['oid'],$_POST['pid'],$_POST['email'],$_POST['aliasemail'],$_POST['forwardemail'],$_POST['inactive'],$_POST['t0'],$_POST['t1'],$_POST['t2'],$_POST['t3'],$_POST['t4'],$_POST['t5'],$_POST['a1'],$_POST['a2'],$_POST['a3'],$_POST['tel1'],$_POST['tel2'],$_POST['tel3']));
            CreateSpecialFoldersForEndpoint($lastRowID);
    }
    else
    {
        // Edit
        if ($_POST['pid'] == $_POST['c'])
            die ("Same PID as EID");
        QQ("UPDATE ENDPOINTS SET NAME = ?,NAMEEN = ?, OID = ?,PARENT = ?,EMAIL = ?,ALIASEMAIL = ?,FORWARDEMAIL = ?,INACTIVE = ?, T0 = ?,T1 = ?,T2 = ?,T3 = ?,T4 = ?,T5 = ?,A1 = ?,A2 = ?,A3 = ?,TEL1 = ?,TEL2 = ?,TEL3 = ? WHERE ID = ?",array($_POST['name'],$_POST['nameen'],$_POST['oid'],$_POST['pid'],$_POST['email'],$_POST['aliasemail'],$_POST['forwardemail'],$_POST['inactive'],$_POST['t0'],$_POST['t1'],$_POST['t2'],$_POST['t3'],$_POST['t4'],$_POST['t5'],$_POST['a1'],$_POST['a2'],$_POST['a3'],$_POST['tel1'],$_POST['tel2'],$_POST['tel3'],$_POST['c']));
        CreateSpecialFoldersForEndpoint($_POST['c']);
    }
    redirect("endpoints.php");
    die;
}

function CreateOrEditEndpoint($eid)
{
    global $u;
    $r = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($eid))->fetchArray();

    // Has access ?
    $a = 0;
    if ($r)    
        $a = UserAccessEP($r['ID'],$u->uid);
    else
    if (count($u->fadmin))
        $a = 2;
    else
    if ($u->superadmin)
        $a = 2;

    if ($a != 2)
        return;

    if (!$r)
        $r = array("ID" => 0, "OID" => 0,"PARENT" => "","NAME" => "", "NAMEEN" => "","EMAIL" => "","ALIASEMAIL" => "","FORWARDEMAIL" => "", "INACTIVE" => 0,"T0" => "","T1" => "","T2" => "", "T3" => "","T4" => "","T5" => "","A1" => "","A2" => "","A3" => "","TEL1" => "","TEL2" => "","TEL3" => "");

    ?>
    <form method="POST" action="endpoints.php">
    <input type="hidden" name="c" value="<?= $r['ID'] ?>" />

    <article class="message is-dark">
  <div class="message-header">
    <p>Βασικά Στοιχεία</p>
  </div>
  <div class="message-body">

    Όνομα: <br>
    <input type="text" class="input" name="name" value="<?= $r['NAME']?>" required/>
    <br><br>
    Όνομα στα Αγγλικά: <br>
    <input type="text" class="input" name="nameen" value="<?= $r['NAMEEN']?>" required/>

    <?php
//function PickEP($name = "eid",$sel = array(),$m = 0,$foruonly = 0,$readonly = 0,$oidx = 0,$andnone = 0,$req_level = 1)
 
        printf('<br><br>Φορέας: %s',PickOrganization("oid",array($r['OID']),0,$u->uid));
        printf('<br><br>Κάτω από endpoint: %s<br><br><br>',PickEP("pid",array($r['PARENT']),0,$u->uid,0,0,1,1));

    ?>


    E-mail: <br>
    <input type="email" class="input" name="email" value="<?= $r['EMAIL']?>" required/>
<br><br>
    Εικονικό E-mail για είσοδο από mails: <br>
    <input type="email" class="input" name="aliasemail" value="<?= $r['ALIASEMAIL']?>" />
    <br><br>
    E-mail για προώθηση όλων των εισερχομένων: <br>
    <input type="email" class="input" name="forwardemail" value="<?= $r['FORWARDEMAIL']?>" />


    <br><br>
    Ενεργό στο KΣΗΔΕ<br>
    <select name="inactive" class="input">
        <option value="0" <?= $r['INACTIVE'] != 1 ? "selected" : ""?>>Ναι</option>
        <option value="1" <?= $r['INACTIVE'] == 1 ? "selected" : ""?>>Όχι</option>
    </select>

</div></article>


<article class="message is-dark">
  <div class="message-header">
    <p>Λογότυπο</p>
  </div>
  <div class="message-body">

    Eπίπεδο 0: <br>
    <input type="text" class="input" name="t0" value="<?= $r['T0']?>" required/>
    Eπίπεδο 1: <br>
    <input type="text" class="input" name="t1" value="<?= $r['T1']?>" />
    Eπίπεδο 2: <br>
    <input type="text" class="input" name="t2" value="<?= $r['T2']?>" />
    Eπίπεδο 3: <br>
    <input type="text" class="input" name="t3" value="<?= $r['T3']?>" />
    Eπίπεδο 4: <br>
    <input type="text" class="input" name="t4" value="<?= $r['T4']?>" />
    Eπίπεδο 5: <br>
    <input type="text" class="input" name="t5" value="<?= $r['T5']?>" />
    <br>
    <br>

    Διεύθυνση: <br>
    <input type="text" class="input" name="a1" value="<?= $r['A1']?>" required/>
    TK: <br>
    <input type="text" class="input" name="a2" value="<?= $r['A2']?>" required/>
    Πόλη: <br>
    <input type="text" class="input" name="a3" value="<?= $r['A3']?>" required/>

    <br><br>
    Τηλέφωνο 1: <br>
    <input type="text" class="input" name="tel1" value="<?= $r['TEL1']?>" required/>
    Τηλέφωνο 2: <br>
    <input type="text" class="input" name="tel2" value="<?= $r['TEL2']?>" />
    Τηλέφωνο 3: <br>
    <input type="text" class="input" name="tel3" value="<?= $r['TEL3']?>" />

    <br><br>
</div></article>

    <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="endpoints.php" class="autobutton button is-danger">Άκυρο</button>
    <?php
}

function PrintEndpoints($oid)
{
    global $u;
    $q = QQ("SELECT * FROM ENDPOINTS ORDER BY OID ASC, PARENT ASC,ID ASC,NAME ASC");
    ?>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th class="all">Φορέας</th>
        <th class="all">Όνομα</th>
        <th  class="all">e-mail</th>
        <th  class="all">Κάτω από</th>
        <th  class="all">Επίπεδα</th>
        <th  class="all">Διεύθυνση</th>
        <th  class="all">Τηλέφωνα</th>
        <th  class="all">Επιλογές</th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        $a = UserAccessEP($r['ID'],$u->uid);
        if ($a == 0)
            continue;
        printf('<tr>');

        if ($oid && $oid != $r['OID'])
            continue;
        printf('<td>%s</td>',$r['ID']);
        $or = FRow($r['OID']);
        printf('<td>%s</td>',$or['NAME']);
        printf('<td>%s<br>%s<br>%s</td>',$r['NAME'],$r['NAMEEN'],$r['INACTIVE'] == 1 ? "Ανενεργό" : "Ενεργό") ;
        printf('<td>%s<br>%s<br>%s</td>',$r['EMAIL'],$r['ALIASEMAIL'],$r['FORWARDEMAIL'] && strlen($r['FORWARDEMAIL']) ? '=>'.$r['FORWARDEMAIL'] : '');
        if ($r['PARENT'] == 0)
            printf('<td></td>');
        else
        {
            $epp = EPRow($r['PARENT']);
            printf('<td>%s</td>',$epp['NAME']);
        }


        printf('<td>');
        for($i = 0 ; $i < 10 ; $i++)
        {
            if ($r["T$i"] == "")
                break;
            printf("%s<br>",$r["T$i"]);
        }
        printf('</td>');
        printf('<td>%s<br>%s<br>%s</td>',$r['A1'],$r['A2'],$r['A3']);
        printf('<td>%s<br>%s<br>%s</td>',$r['TEL1'],$r['TEL2'],$r['TEL3']);
        printf('<td>');
        if ($a == 2)
            printf('<a href="endpoints.php?eid=%s">Επεξεργασία</a> &mdash; <a href="folders.php?eid=%s">Φάκελοι</a> &mdash; <a href="rules.php?eid=%s">Κανόνες</a> &mdash; <a href="restrictions.php?eid=%s">Περιορισμοί</a>',
            $r['ID'],$r['ID'],$r['ID'],$r['ID']);
        if ($a == 2)
            printf(' &mdash; <a href="endpoints.php?delete=%s"><font color="red">Διαγραφή</font></a>',$r['ID']);
        printf('</td>');

        printf('</tr>');
    }
    ?>
    </tbody>
    </table>
    <?php
}

require_once "output.php";
echo '<div class="content" style="margin: 20px">';
if (array_key_exists("eid",$req))
{
    CreateOrEditEndpoint($req['eid']);
}
else
{
    if ($u->superadmin || count($u->fadmin))
        PrintHeader('index.php','&nbsp; <button class="button is-primary autobutton block" href="endpoints.php?eid=0">Νέο Endpoint</button>');
    else
        PrintHeader('index.php');
    $oid = 0;
    if (array_key_exists("oid",$req))
        $oid = $req['oid'];
    PrintEndpoints($oid);
}
?>

</div>