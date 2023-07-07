<?php

require_once "functions.php";
if (!$u)
    diez();
$lid = 0;
$eid = 0;
if (array_key_exists("eid",$req))
    $eid = $req['eid'];
if (array_key_exists("lid",$req))
    $lid = $req['lid'];
if ($eid)
    $a = UserAccessEP($eid,$u->uid);
if ($lid)
    $a = UserAccessLocker($lid,$u->uid);
if ($a != 2)
    diez();

require_once "output.php";


if (array_key_exists("delete",$req))
{
    if (array_key_exists("force",$req) && UserAccessFolder($req['delete'],$u->uid) == 2)
    {
        QQ("BEGIN TRANSACTION");
        DeleteWholeFolder($req['delete']);
        QQ("COMMIT");
        redirect(sprintf("folders.php?eid=%s&lid=%s",$eid,$lid));
    }
    echo '<div class="content" style="margin:20px;">';
    echo 'Διαγραφή Φακέλου';

    $docccount = CountDB("DOCUMENTS WHERE FID = ?",array($req['delete']));

    if ($docccount)
        printf("<br><b>Η διαγραφή του φακέλου θα διαγράψει οριστικά και %s έγγραφα που βρίσκονται σε αυτόν!</b>",$docccount);
    printf('<br><br><button href="folders.php?eid=%s&lid=%s&delete=%s&force=1" class="button autobutton is-danger">Επιβεβαίωση Διαγραφής</button><br>',$eid,$lid,$req['delete']);
    printf('<br><hr><button href="folders.php?eid=%s&lid=%s" class="button autobutton is-success">Πίσω</button>',$eid,$lid);
    die;
}

if (array_key_exists("c",$_POST))
{
    $ur = UserRow($u->uid);
    if ($ur['CLASSIFIED'] < $_POST['classification'])
        $_POST['classification'] = 0;

    $what = 0;
    $r = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($_POST['c']))->fetchArray();
    if (!$r)
    {
        // Create
        QQ("INSERT INTO FOLDERS (EID,LID,NAME,PARENT,SPECIALID,CLASSIFIED) VALUES (?,?,?,?,?,?)",array($_POST['eid'],$_POST['lid'],$_POST['name'],$_POST['parent'],0,$_POST['classification']));
        $what = $lastRowID;
    }
    else
    {
        // Edit
        $what = $_POST['c'];
        if ($_POST['parent'] == $_POST['c'])
            $_POST['parent'] = 0;
        QQ("UPDATE FOLDERS SET EID = ?,LID = ?, NAME = ?, PARENT = ?, CLASSIFIED = ? WHERE ID = ? AND SPECIALID = 0",array($_POST['eid'],$_POST['lid'],$_POST['name'],$_POST['parent'],$_POST['classification'],$_POST['c']));
    }

    QQ("DELETE FROM USERSINFOLDER WHERE FID = ?",array($what));
    foreach($req['readuid'] as $wr)
    {
        QQ("INSERT INTO USERSINFOLDER (UID,FID,ACCESS) VALUES (?,?,?)",array($wr,$what,1));        
    }
    foreach($req['writeuid'] as $wr)
    {
        QQ("INSERT INTO USERSINFOLDER (UID,FID,ACCESS) VALUES (?,?,?)",array($wr,$what,2));        
    }

    redirect(sprintf("folders.php?eid=%s&lid=%s",$eid,$lid));
    die;
}

function CreateOrEditFolder($fid)
{
    global $u;
    global $eid;
    global $lid;
    $r = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($fid))->fetchArray();
    if (!$r)
        $r = array("ID" => 0,"NAME" => "", "PARENT" => "","CLASSIFIED" => "","LID" => 0);

    ?>
    <form method="POST" action="folders.php">
    <input type="hidden" name="c" value="<?= $r['ID'] ?>" />
    <input type="hidden" name="eid" value="<?= $eid ?>" />
    <input type="hidden" name="lid" value="<?= $lid ?>" />

    Όνομα: <br>
        <input type="text" class="input" name="name" value="<?= $r['NAME']?>" required/>
    <br><br>
    Μέσα σε: <br>
    
    <?= PickFolder($eid,"parent",array($r['PARENT']),0,0,0,0,$lid) ?>

    <br><br>
    Επιπλέον άτομα με Read Access: <br>
    <?php
    $ar1 = array();
    $qj1 = QQ("SELECT * FROM USERSINFOLDER WHERE FID = ? AND ACCESS = 1",array($fid));
    while($rj1 = $qj1->fetchArray())
        $ar1[] = $rj1['UID'];
    echo  PickUser("readuid[]",$ar1,1,array($eid)); 
    ?>

    <br>
    Επιπλέον άτομα με Write Access: <br>
    <?php
    $ar2 = array();
    $qj2 = QQ("SELECT * FROM USERSINFOLDER WHERE FID = ? AND ACCESS = 2",array($fid));
    while($rj2 = $qj2->fetchArray())
        $ar2[] = $rj2['UID'];
    echo  PickUser("writeuid[]",$ar2,1,array($eid)); 
    ?>

    <br>
    Διαβάθμιση: <br>
        <?php
        echo PickClassification("classification",array($r['CLASSIFIED']));
        ?>

    <br><br>
    <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="folders.php" class="autobutton button is-danger">Άκυρο</button>
    <?php
}

function PrintFolders($rootint = 0,$nest = 0)
{
    global $eid,$lid;
    if ($lid)
    {
        if ($rootint == 0)
            $q = QQ("SELECT * FROM FOLDERS WHERE LID = ? AND (PARENT = 0 OR PARENT IS NULL)",array($lid));
        else
            $q = QQ("SELECT * FROM FOLDERS WHERE LID = ? AND PARENT = ?",array($lid,$rootint));
    }
    else
    {
        if ($rootint == 0)
            $q = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND (PARENT = 0 OR PARENT IS NULL)",array($eid));
        else
            $q = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND PARENT = ?",array($eid,$rootint));

    }
    ?>
    <?php
    if ($nest == 0)
    {
    ?>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th  class="all">Όνομα</th>
    </thead>
    <tbody>
    <?php
    }
    while($r = $q->fetchArray())
    {
    if ($nest == 0)
        {
            printf('<tr>');
            printf('<td>%s</td>',$r['ID']);
            printf('<td>');
            printf('%s',$r['NAME']);
            if ($r['SPECIALID'] == 0)
            {
                printf(' <a href="folders.php?eid=%s&fid=%s&lid=%s"><font color="red">Επεξεργασία</font></a>',$eid,$r['ID'],$lid);
                printf(' <a href="folders.php?eid=%s&delete=%s&lid=%s"><font color="red">Διαγραφή</font></a>',$eid,$r['ID'],$lid);
            }
            PrintFolders($r['ID'],$nest + 1);
            printf('</td>');
            printf('</tr>');
        }
        else
        {
            printf('<br>');
            for($i = 0 ; $i < $nest ; $i++)
                printf('&nbsp;');
            printf('(%s) %s',$r['ID'],$r['NAME']);
            printf(' <a href="folders.php?eid=%s&fid=%s&lid=%s"><font color="red">Επεξεργασία</font></a>',$eid,$r['ID'],$lid);
            printf(' <a href="folders.php?eid=%s&delete=%s&lid=%s"><font color="red">Διαγραφή</font></a>',$eid,$r['ID'],$lid);
            PrintFolders($r['ID'],$nest + 1);
        }
    }
    if ($nest == 0)
    {
    ?>
    </tbody>
    </table>
    <?php
    }
}


echo '<div class="content" style="margin: 20px">';
if (array_key_exists("fid",$req))
{
    CreateOrEditFolder($req['fid']);
}
else
{
    PrintHeader('endpoints.php',sprintf('&nbsp; <button class="button is-primary autobutton block" href="folders.php?fid=0&eid=%s&lid=%s">Νέος Φάκελος</button>',$eid,$lid));
    PrintFolders();
}
?>
</div>