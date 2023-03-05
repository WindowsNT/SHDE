<?php

require_once "functions.php";
if (!$u)
    diez();
if (!$u->superadmin)
    diez();


    if (array_key_exists("delete",$req))
    {
        $r = QQ("DELETE FROM USERS WHERE ID = ?",array($req['delete']))->fetchArray();
        redirect("users.php");
        die;
    }

    if (array_key_exists("c",$_POST))
    {
        $ur = UserRow($u->uid);
    
        $r = QQ("SELECT * FROM USERS WHERE ID = ?",array($_POST['c']))->fetchArray();
        if (!$r)
        {
            // Create
            QQ("INSERT INTO USERS (USERNAME,LASTNAME,FIRSTNAME,TITLE,CLASSIFIED,EMAIL) VALUES (?,?,?,?,?,?)",array($_POST['username'],$_POST['lastname'],$_POST['firstname'],$_POST['title'],$_POST['classification'],$_POST['email']));
        }
        else
        {
            // Edit
            QQ("UPDATE USERS SET USERNAME = ?,LASTNAME = ?, FIRSTNAME = ?, TITLE = ?,EMAIL = ?,CLASSIFIED = ? WHERE ID = ?",array($_POST['username'],$_POST['lastname'],$_POST['firstname'],$_POST['title'],$_POST['email'],$_POST['classification'],$_POST['c']));
        }
        redirect("users.php");
        die;
    }
    
    function CreateOrEditUser($uid)
    {
        $r = QQ("SELECT * FROM USERS WHERE ID = ?",array($uid))->fetchArray();
        if (!$r)
            $r = array("ID" => 0, "USERNAME" => "", "LASTNAME" => "", "FIRSTNAME" => "","TITLE" => "", "EMAIL" => "","CLASSIFIED" => "");
     
        ?>
        <form method="POST" action="users.php">
        <input type="hidden" name="c" value="<?= $r['ID'] ?>" />

        Username: <br>
        <input type="text" class="input" name="username" value="<?= $r['USERNAME']?>" required/>

        Επίθετο: <br>
        <input type="text" class="input" name="lastname" value="<?= $r['LASTNAME']?>" required/>

        Όνομα: <br>
        <input type="text" class="input" name="firstname" value="<?= $r['FIRSTNAME']?>" required/>

        Τίτλος: <br>
        <input type="text" class="input" name="title" value="<?= $r['TITLE']?>" required/>

        Email: <br>
        <input type="email" class="input" name="email" value="<?= $r['EMAIL']?>" required/>

        Διαβάθμιση μέχρι: <br>
        <?php
        echo PickClassification("classification",array($r['CLASSIFIED']));
        ?>

        <br><br>
        <button class="button is-primary">Υποβολή</button>
        </form>
        <button href="users.php" class="autobutton button is-danger">Άκυρο</button>
        <?php
    }
    

function PrintUsers()
{
    $q = QQ("SELECT * FROM USERS");
    ?>
    <script>
        function del(id)
        {
            if (confirm('Σίγουρα;'))
            {
                window.location = "users.php?delete=" + id;
            }
        }
    </script>
    <hr>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th class="all">Username</th>
        <th  class="all">Επίθετο</th>
        <th  class="all">Όνομα</th>
        <th  class="all">Τίτλος</th>
        <th  class="all">E-mail</th>
        <th  class="all">Διαβάθμιση</th>
        <th  class="all">Επιλογές</th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        printf('<tr>');

        printf('<td>%s</td>',$r['ID']);
        printf('<td>%s</td>',$r['USERNAME']);
        printf('<td>%s</td>',$r['LASTNAME']);
        printf('<td>%s</td>',$r['FIRSTNAME']);
        printf('<td>%s</td>',$r['TITLE']);
        printf('<td>%s</td>',$r['EMAIL']);
        printf('<td>%s</td>',ClassificationString($r['CLASSIFIED']));
        printf('<td><a href="users.php?uid=%s">Επεξεργασία</a> &mdash; <a href="apikeys.php?uid=%s">API Keys</a> &mdash; <a href="javascript:del(%s);">Διαγραφή</a></td>',$r['ID'],$r['ID'],$r['ID']);
        printf('</tr>');
    }
    ?>
    </tbody>
    </table>
    <?php
}

require_once "output.php";
echo '<div class="content" style="margin: 20px">';
if (array_key_exists("uid",$req))
{
    CreateOrEditUser($req['uid']);
}
else
{
    PrintHeader('index.php');
    echo '<button class="button is-primary is-small autobutton" href="users.php?uid=0">Νέος Χρήστης</button>';
    PrintUsers();
}
?>

</div>