<?php

require_once "functions.php";
if (!$u)
    diez();
if (!array_key_exists("eid",$req))
    diez();
$eid = $req['eid'];
$a = UserAccessEP($eid,$u->uid);
require_once "output.php";


if (array_key_exists("delete",$req))
{
    if (array_key_exists("force",$req))
    {
        QQ("BEGIN TRANSACTION");
        DeleteWholeLocker($req['delete']);
        QQ("COMMIT");
        redirect(sprintf("lockers.php?eid=%s",$eid));
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
        QQ("INSERT INTO LOCKERS (EID,NAME) VALUES (?,?)",array($_POST['eid'],$_POST['name']));
        $lid = $lastRowID;
    }
    else
    {
        // Edit
        QQ("UPDATE LOCKERS SET EID = ?, NAME = ? WHERE ID = ?",array($_POST['eid'],$_POST['name'],$_POST['c']));
        $lid = $_POST['c'];
    }

    QQ("DELETE * FROM USERSINLOCKER WHERE LID = ?",array($lid));
    foreach($_POST['uid'] as $uid)
        QQ("INSERT INTO USERSINLOCKER (UID,LID,ACCESS) VALUES (?,?,?)",array($uid,$lid,2));
    QQ("COMMIT");
    redirect(sprintf("lockers.php?eid=%s",$eid));
    die;
}

function CreateOrEditLocker($lid)
{
    global $u;
    global $eid;
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

    Όνομα: <br>
        <input type="text" class="input" name="name" value="<?= $r['NAME']?>" required/>
    <br><br>
<?php
    $foreis = array();
// function PickUser($name = "uid",$sel = array(),$m = 0,$foreis = array(),$andall = 0,$uid = 0,$eps = array())
    echo  PickUser("uid[]",$users,1,array(),0,0,array($eid)); 
?>
    <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="lockers.php?eid=<?= $eid ?>" class="autobutton button is-danger">Άκυρο</button>
    <?php
}


function PrintLockers()
{
    global $eid;
    $q = QQ("SELECT * FROM LOCKERS WHERE EID = ?",array($eid));
    ?>
    <?php
    ?>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th  class="all">Όνομα</th>
        <th  class="all">Μέλη</th>
        <th  class="all"></th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        printf('<tr>');
        printf('<td>%s</td>',$r['ID']);
        printf('<td>');
        printf('%s</td>',$r['NAME']);

        $x1 = QQ("SELECT * FROM USERSINLOCKER WHERE LID = ?",array($r['ID']));
        printf('<td>');
        while($x2 = $x1->fetchArray())
            {
                $ur = UserRow($x2['UID']);
                printf('%s %s<br>',$ur['LASTNAME'],$ur['FIRSTNAME']);
            }
        printf('</td>');
    
        

        printf('<td><a href="lockers.php?eid=%s&lid=%s"><font color="red">Επεξεργασία</font></a>',$eid,$r['ID']);
        printf(' <a href="lockers.php?eid=%s&delete=%s"><font color="red">Διαγραφή</font></a>',$eid,$r['ID']);
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
PrintHeader('endpoints.php',sprintf('&nbsp; <button class="button is-primary autobutton" href="lockers.php?lid=0&eid=%s">Νέα Θυρίδα</button>',$req['eid']));
PrintLockers();
}


