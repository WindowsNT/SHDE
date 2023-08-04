<?php

require_once "functions.php";
if (!$u)
    diez();
if (!$u->superadmin && count($u->epadmin) == 0 && count($u->fadmin) == 0)
    diez();


require_once "not.php";
NOT_Scripts();

function WriteAccess()
{
    global $u;   
    $a = 0;
    if ($u->superadmin)
        $a = 1;
    if (in_array($_POST['oid'],$u->fadmin))
        $a = 1;
    return $a;
}


    if (array_key_exists("delete",$req))
    {        
        if (!WriteAccess())
            die;

        $r = QQ("DELETE FROM ROLES WHERE ID = ?",array($req['delete']))->fetchArray();
        redirect("roles.php");
        die;
    }

    if (array_key_exists("c",$_POST))
    {
        if (!WriteAccess())
            die;

        if ($_POST['role'] == ROLE_FADMIN)
            $_POST['eid'] = 0;

        $er = EPRow($_POST['eid']);
        if ($er && $er['OID'] != $_POST['oid'])
            die ("EID does not belong to OID");


        $r = QQ("SELECT * FROM ROLES WHERE ID = ?",array($_POST['c']))->fetchArray();
        if (!$r)
        {
            // Create
            QQ("INSERT INTO ROLES (UID,ROLEID,EID,OID) VALUES (?,?,?,?)",array($_POST['uid'],$_POST['role'],$_POST['eid'],$_POST['oid']));
            PushMany(array($_POST['uid']),sprintf("Αλλαγή του ρόλου σας."));            
        }
        else
        {
            // Edit
            PushMany(array($_POST['uid']),sprintf("Αλλαγή του ρόλου σας."));            
            QQ("UPDATE ROLES SET UID = ?, ROLEID = ?, EID = ?,OID = ? WHERE ID = ?",array($_POST['uid'],$_POST['role'],$_POST['eid'],$_POST['oid'],$_POST['c']));
        }
        redirect("roles.php");
        die;
    }
    
    function CreateOrEditRole($rid)
    {
        global $u;
        $r = QQ("SELECT * FROM ROLES WHERE ID = ?",array($rid))->fetchArray();
        if (!$r)
            $r = array("ID" => 0, "UID" => "", "ROLEID" => "", "EID" => "","OID" => "");
    
        ?>
        <form method="POST" action="roles.php">
        <input type="hidden" name="c" value="<?= $r['ID'] ?>" />
        <input type="hidden" name="oid" value="<?= $r['OID'] ?>" />
    
        Χρήστης:
        <?php
        $foreis = array();
        if (!$u->superadmin)
        {
            $q5 = QQ("SELECT * FROM ROLES WHERE UID = ? AND ROLEID = ?",array($u->uid,ROLE_FADMIN));
            while($r5 = $q5->fetchArray())
                $foreis[] = $r5['OID'];
            $q5 = QQ("SELECT * FROM ROLES WHERE UID = ? AND ROLEID = ?",array($u->uid,ROLE_EPADMIN));
            while($r5 = $q5->fetchArray())
                $foreis[] = $r5['OID'];
            }
        echo  PickUser("uid",array($r['UID']),0,$foreis); 
        ?>
        <br>
        <br>


        <?php
            printf('Φορέας: %s<br><br>',PickOrganization("oid",array($r['OID']),0,$u->uid));
            printf('Endpoint: %s<br><br>', PickEP("eid",array($r['EID']),0,$u->uid,0,0,$u->superadmin,2));
        ?>



        Ρόλος:
        <select name="role" class="input chosen-select">
        <option value="<?= ROLE_USER ?>" <?= $r['ROLEID'] == ROLE_USER ? "selected" : "" ?>>Μέλος</option>
            <option value="<?= ROLE_EDITOR ?>" <?= $r['ROLEID'] == ROLE_EDITOR ? "selected" : "" ?>>Συντάκτης</option>
            <option value="<?= ROLE_EPADMIN ?>" <?= $r['ROLEID'] == ROLE_EPADMIN ? "selected" : "" ?>>Διαχειριστής Endpoint</option>
            <?php        
        if ($u->superadmin)
        {
        ?>
            <option value="<?= ROLE_FADMIN ?>" <?= $r['ROLEID'] == ROLE_FADMIN ? "selected" : "" ?>>Διαχειριστής Φορέα</option>
        <?php
        }
        ?>
            <option value="<?= ROLE_SIGNER0 ?>" <?= $r['ROLEID'] == ROLE_SIGNER0 ? "selected" : "" ?>>Τελικός Υπογράφων</option>
        </select>
        <br><br>

        <br><br>
        <button class="button is-primary">Υποβολή</button>
        </form>
        <button href="roles.php" class="autobutton button is-danger">Άκυρο</button>
        <?php
    }
    

function PrintRoles()
{
    global $u;
    $q = QQ("SELECT * FROM ROLES");
    ?>
    <script>
        function del(id)
        {
            if (confirm('Σίγουρα;'))
            {
                window.location = "roles.php?delete=" + id;
            }
        }
    </script>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th class="all">UID</th>
        <th  class="all">Ρόλος</th>
        <th  class="all">Φορέας</th>
        <th  class="all">Endpoint</th>
        <th  class="all">Επιλογές</th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        $a = 0;
        if ($u->superadmin)
            $a = 2;

        if (in_array($r['OID'],$u->fadmin))
            {
                $a = 1;
                if ($r['UID'] != $u->uid)
                    $a = 2;
            }
        if (in_array($r['EID'],$u->epadmin))
            $a = 1;

        if ($a == 0)
            continue;   

        printf('<tr>');

        printf('<td>%s</td>',$r['ID']);
        $u1 = UserRow($r['UID']);
        $e1 = EPRow($r['EID']);
        $f1 = FRow($r['OID']);
        printf('<td>%s &mdash; %s %s</td>',$r['UID'],$u1['LASTNAME'],$u1['FIRSTNAME']);
        if ($r['ROLEID'] == ROLE_EDITOR)            printf('<td>Συντάκτης</td>');
        if ($r['ROLEID'] == ROLE_EPADMIN)            printf('<td>Διαχειριστής EndPoint</td>');
        if ($r['ROLEID'] == ROLE_FADMIN)            printf('<td>Διαχειριστής Φορέα</td>');
        if ($r['ROLEID'] == ROLE_SIGNER0)            printf('<td>Τελικός Υπογράφων</td>');
        if ($f1)
            printf('<td>%s  &mdash; %s</td>',$r['OID'],$f1['NAME']);
        else
            printf('<td></td>',$r['OID'],$f1 ? $f1['NAME'] : "");
        if ($e1)
            printf('<td>%s  &mdash; %s</td>',$r['EID'],$e1['NAME']);
        else
            printf('<td></td>');
        printf('<td>');
        if ($a == 2)
            printf('<a href="roles.php?rid=%s">Επεξεργασία</a> ',$r['ID']);
        if ($a == 2)
            printf('<a href="javascript:del(%s);">Διαγραφή</a>',$r['ID']);
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
if (array_key_exists("rid",$req))
{
    CreateOrEditRole($req['rid']);
}
else
{
    PrintHeader('index.php','&nbsp; <button class="button is-primary block autobutton" href="roles.php?rid=0">Νέο Role</button> ');
    PrintRoles();
}
?>

</div>