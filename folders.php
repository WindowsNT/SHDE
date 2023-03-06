<?php

require_once "functions.php";
if (!$u)
    diez();
if (!array_key_exists("eid",$req))
    diez();
$eid = $req['eid'];
$a = UserAccessEP($eid,$u->uid);
if ($a != 2)
    diez();

require_once "output.php";



if (array_key_exists("delete",$req))
{
    if (array_key_exists("force",$req))
    {
        QQ("BEGIN TRANSACTION");
        DeleteWholeFolder($req['delete']);
        QQ("COMMIT");
        redirect(sprintf("folders.php?eid=%s",$eid));
    }
    echo '<div class="content" style="margin:20px;">';
    echo 'Διαγραφή Φακέλου';

    $docccount = CountDB("DOCUMENTS WHERE FID = ?",array($req['delete']));

    if ($docccount)
        printf("<br><b>Η διαγραφή του φακέλου θα διαγράψει οριστικά και %s έγγραφα που βρίσκονται σε αυτόν!</b>",$docccount);
    printf('<br><br><button href="folders.php?eid=%s&delete=%s&force=1" class="button autobutton is-danger">Επιβεβαίωση Διαγραφής</button><br>',$req['eid'],$req['delete']);
    printf('<br><hr><button href="folders.php?eid=%s" class="button autobutton is-success">Πίσω</button>',$req['eid']);
    die;
}

if (array_key_exists("c",$_POST))
{
    $ur = UserRow($u->uid);
    if ($ur['CLASSIFIED'] < $_POST['classification'])
        $_POST['classification'] = 0;

    $r = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($_POST['c']))->fetchArray();
    if (!$r)
    {
        // Create
        QQ("INSERT INTO FOLDERS (EID,NAME,PARENT,SPECIALID,CLASSIFIED) VALUES (?,?,?,?,?)",array($_POST['eid'],$_POST['name'],$_POST['parent'],0,$_POST['classification']));
    }
    else
    {
        // Edit
        if ($_POST['parent'] == $_POST['c'])
            $_POST['parent'] = 0;
        QQ("UPDATE FOLDERS SET EID = ?, NAME = ?, PARENT = ?, CLASSIFIED = ? WHERE ID = ? AND SPECIALID = 0",array($_POST['eid'],$_POST['name'],$_POST['parent'],$_POST['classification'],$_POST['c']));
    }
    redirect(sprintf("folders.php?eid=%s",$eid));
    die;
}

function CreateOrEditFolder($fid)
{
    global $u;
    global $eid;
    $r = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($fid))->fetchArray();
    if (!$r)
        $r = array("ID" => 0,"NAME" => "", "PARENT" => "","CLASSIFIED" => "");

    ?>
    <form method="POST" action="folders.php">
    <input type="hidden" name="c" value="<?= $r['ID'] ?>" />
    <input type="hidden" name="eid" value="<?= $eid ?>" />

    Όνομα: <br>
        <input type="text" class="input" name="name" value="<?= $r['NAME']?>" required/>
    <br><br>
    Μέσα σε: <br>
    
    <?= PickFolder($eid,"parent",array($r['PARENT'])) ?>

    <br><br>
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
    global $eid;
    if ($rootint == 0)
        $q = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND (PARENT = 0 OR PARENT IS NULL)",array($eid));
    else
        $q = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND PARENT = ?",array($eid,$rootint));
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
                printf(' <a href="folders.php?eid=%s&fid=%s"><font color="red">Επεξεργασία</font></a>',$eid,$r['ID']);
                printf(' <a href="folders.php?eid=%s&delete=%s"><font color="red">Διαγραφή</font></a>',$eid,$r['ID']);
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
            printf(' <a href="folders.php?eid=%s&fid=%s"><font color="red">Επεξεργασία</font></a>',$eid,$r['ID']);
            printf(' <a href="folders.php?eid=%s&delete=%s"><font color="red">Διαγραφή</font></a>',$eid,$r['ID']);
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
    PrintHeader('endpoints.php',sprintf('&nbsp; <button class="button is-primary is-small autobutton" href="folders.php?fid=0&eid=%s">Νέος Φάκελος</button>',$req['eid']));
    PrintFolders();
}
?>
</div>