<?php

require_once "functions.php";
if (!$u)
    diez();
$eid = 0;
$oid = 0;
if (array_key_exists("eid",$req))
    $eid = $req['eid'];
if (array_key_exists("oid",$req))
    $oid = $req['oid'];
if ($eid == 0 && $oid == 0)
    diez();

if ($eid && !$oid)
{
    $epr = EPRow($eid);
    $oid = $epr['OID'];
}

if ($oid != 0 && UserAccessOID($oid,$u->uid) != 2)
    diez();
else
if ($eid != 0 && UserAccessEP($eid,$u->uid) != 2)
    diez();
require_once "output.php";


if (array_key_exists("delete",$req))
{
    if (array_key_exists("force",$req) && UserAccessLocker($eid,$oid) == 2)
    {

        QQ("BEGIN TRANSACTION");
        DeleteWholeLocker($req['delete']);
        QQ("COMMIT");
        redirect(sprintf("lockers.php?eid=%s&oid=%s",$eid,$oid));
        die;
    }
    echo '<div class="content" style="margin:20px;">';
    echo 'Διαγραφή Θυρίδας';

    $fcount = CountDB("FOLDERS WHERE LID = ?",array($req['delete']));

    if ($fcount) 
        printf("<br><b>Η διαγραφή της θυρίδας θα διαγράψει οριστικά και %s φακέλους!</b>",$fcount);

    printf('<br><br><button href="lockers.php?delete=%s&force=1&eid=%s" class="button autobutton is-danger">Επιβεβαίωση Διαγραφής</button><br>',$req['delete'],$eid);
    printf('<br><hr><button href="lockers.php?eid=%s" class="button autobutton is-success">Πίσω</button>',$eid);

    die;
}

if (array_key_exists("c",$_POST))
{
    QQ("BEGIN TRANSACTION");
    $r = QQ("SELECT * FROM LOCKERS WHERE ID = ?",array($_POST['c']))->fetchArray();
    if (!$r)
    {
        // Create
        QQ("INSERT INTO LOCKERS (OID,EID,NAME) VALUES (?,?,?)",array($_POST['oid'],$_POST['eid'],$_POST['name']));
        $lid = $lastRowID;
        CreateSpecialFoldersForLocker($lid);
    }
    else
    {
        // Edit
        QQ("UPDATE LOCKERS SET OID = ?,EID = ?, NAME = ? WHERE ID = ?",array($_POST['oid'],$_POST['eid'],$_POST['name'],$_POST['c']));
        $lid = $_POST['c'];
    }

    QQ("DELETE FROM USERSINLOCKER WHERE LID = ?",array($lid));
    foreach($_POST['uid'] as $uid)
        QQ("INSERT INTO USERSINLOCKER (UID,LID,ACCESS) VALUES (?,?,?)",array($uid,$lid,2));
    QQ("COMMIT");
    redirect(sprintf("lockers.php?eid=%s&oid=%s",$eid,$oid));
    die;
}

function CreateOrEditLocker($lid)
{
    global $u;
    global $eid;
    global $oid;
    $r = QQ("SELECT * FROM LOCKERS WHERE ID = ?",array($lid))->fetchArray();
    if (!$r)
        $r = array("ID" => 0,"NAME" => "");
    $users = array();
    if ($lid)
    {
        $x1 = QQ("SELECT * FROM USERSINLOCKER WHERE LID = ?",array($lid));
        while($x2 = $x1->fetchArray())
            $users[] = $x2['UID'];
    }
    ?>
    <form method="POST" action="lockers.php">
    <input type="hidden" name="c" value="<?= $r['ID'] ?>" />
    <input type="hidden" name="eid" value="<?= $eid ?>" />
    <input type="hidden" name="oid" value="<?= $oid ?>" />

    Όνομα: <br>
        <input type="text" class="input" name="name" value="<?= $r['NAME']?>" required/>
    <br><br>
    Χρήστες: <br>
<?php
// function PickUser($name = "uid",$sel = array(),$m = 0,$foreis = array(),$andall = 0,$uid = 0,$eps = array())
    echo  PickUser("uid[]",$users,1,$eid == 0 ? array($oid) : array(),0,0,$eid ? array($eid) : array()); 
?>
    <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="lockers.php?eid=<?= $eid ?>" class="autobutton button is-danger">Άκυρο</button>
    <?php
}


function PrintLockers()
{
    global $eid,$oid;
    if ($eid)
        $q = QQ("SELECT * FROM LOCKERS WHERE EID = ?",array($eid));
    else
    if ($oid)
        $q = QQ("SELECT * FROM LOCKERS WHERE OID = ?",array($oid));
    ?>
    <?php
    ?>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th  class="all">Όνομα</th>
        <th  class="all">Φορέας</th>
        <th  class="all">EndPoint</th>
        <th  class="all">Μέλη</th>
        <th  class="all"></th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        $eprow = null;
        $frow = null;
        if ($r['EID'])
            $eprow = EPRow($r['EID']);
        if ($r['OID'])
            $frow = FRow($r['OID']);
        if ($eprow && !$frow)
            $frow = FRow($eprow['OID']);

        printf('<tr>');
        printf('<td>%s</td>',$r['ID']);
        printf('<td>');
        printf('%s</td>',$r['NAME']);
        printf('<td>%s</td>',$frow ? $frow['NAME'] : '');
        printf('<td>%s</td>',$eprow ? $eprow['NAME'] : '');

        $x1 = QQ("SELECT * FROM USERSINLOCKER WHERE LID = ?",array($r['ID']));
        printf('<td>');
        while($x2 = $x1->fetchArray())
            {
                $ur = UserRow($x2['UID']);
                printf('%s %s<br>',$ur['LASTNAME'],$ur['FIRSTNAME']);
            }
        printf('</td>');
    
        

        printf('<td><a href="lockers.php?eid=%s&oid=%s&lid=%s"><font color="red">Επεξεργασία</font></a>',$eid,$oid,$r['ID']);
        printf(' <a href="folders.php?lid=%s">Φάκελοι</a>',$r['ID']);
        printf(' <a href="lockers.php?eid=%s&oid=%s&delete=%s"><font color="red">Διαγραφή</font></a>',$eid,$oid,$r['ID']);
        printf('</td>');
        printf('</tr>');
    }
    ?>
    </tbody>
    </table>
    <?php
}
echo '<div class="content" style="margin: 20px">';
if (array_key_exists("lid",$req))
{
    CreateOrEditLocker($req['lid']);
}
else
{
PrintHeader('endpoints.php',sprintf('&nbsp; <button class="button is-primary autobutton block" href="lockers.php?lid=0&eid=%s&oid=%s">Νέα Θυρίδα</button>',$eid,$oid));
PrintLockers();
}


