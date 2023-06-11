<?php

require_once "functions.php";
if (!$u)
    diez();
if (!$u->superadmin)
    diez();
if (!array_key_exists("uid",$req))
    $req['uid'] = 0;

    if (array_key_exists("delete",$req))
    {        
        $r = QQ("DELETE FROM APIKEYS WHERE ID = ?",array($req['delete']))->fetchArray();
        redirect(sprintf("apikeys.php?uid=%s",$req['uid']));
        die;
    }

    if (array_key_exists("c",$_POST))
    {
        $r = QQ("SELECT * FROM APIKEYS WHERE ID = ?",array($_POST['c']))->fetchArray();
        if (!$r)
        {
            // Create
            QQ("INSERT INTO APIKEYS (UID,T1) VALUES (?,?)",array($_POST['uid'],guidv4()));
        }
        else
        {
            // Edit
        }
        redirect(sprintf("apikeys.php?uid=%s",$req['uid']));
        die;
    }
    
    function CreateOrEditAPIKey($kid,$uid)
    {
        global $req;
        if ($uid)
        {
            QQ("INSERT INTO APIKEYS (UID,T1) VALUES (?,?)",array($uid,guidv4()));
            redirect(sprintf("apikeys.php?uid=%s",$uid));
            die;
        }
        $r = QQ("SELECT * FROM APIKEYS WHERE ID = ?",array($kid))->fetchArray();
        if (!$r)
            $r = array("ID" => 0, "UID" => "", "T1" => "");
    
        ?>
        <form method="POST" action="apikeys.php">
        <input type="hidden" name="c" value="<?= $r['ID'] ?>" />
        <input type="hidden" name="uid" value="<?= $req['uid'] ?>" />
    
        Χρήστης:
        <?php
        $foreis = array();
        echo  PickUser("uid",array($r['UID']),0,$foreis); 
        ?>
        <br>
        <br>

        <br><br>
        <button class="button is-primary">Υποβολή</button>
        </form>
        <button href="apikeys.php" class="autobutton button is-danger">Άκυρο</button>
        <?php
    }
    

function PrintAPIKeys()
{
    global $u;
    global $req;
    if ($req['uid'] > 0)
        $q = QQ("SELECT * FROM APIKEYS WHERE UID = ?",array($req['uid']));
    else
        $q = QQ("SELECT * FROM APIKEYS");
    ?>
    <script>
        function del(id)
        {
            if (confirm('Σίγουρα;'))
            {   

                window.location = "apikeys.php?uid=" + "<?= $req['uid'] ?>" + "&delete=" + id;
            }
        }
        function test(id)
        {
            $.ajax({
                    url: "api.php",
                    method: "POST",
                    data: {"key" : id,"f" : "test"},
                    success: function (result) {
                        alert(JSON.stringify(result));
                    }
                });

        }
    </script>
    <hr>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th class="all">UID</th>
        <th  class="all">Key</th>
        <th  class="all">Επιλογές</th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        $a = 0;
        if ($u->superadmin)
            $a = 2;
        printf('<tr>');
        printf('<td>%s</td>',$r['ID']);
        $u1 = UserRow($r['UID']);
        printf('<td>%s &mdash; %s %s</td>',$r['UID'],$u1['LASTNAME'],$u1['FIRSTNAME']);
        printf('<td>%s</td>',$r['T1']);
        printf('<td>');
        printf('<a href="javascript:test(\'%s\');">Δοκιμή</a> &mdash; <a href="javascript:del(%s);">Διαγραφή</a>',$r['T1'],$r['ID']);
        printf('</td>');
        printf('</tr>');
    }
    ?>
    </tbody>
    </table>
    <?php
}

require_once "output.php";

if (array_key_exists("uid",$req))
    $kk = sprintf(' &nbsp; <button class="button is-primary block autobutton" href="apikeys.php?kid=0&uid=%s">Νέο API Key</button> ',$req['uid']);
else
    $kk = sprintf('<button class="button is-primary block autobutton" href="apikeys.php?kid=0">Νέο API Key</button>');

if (array_key_exists("kid",$req))
{
    PrintHeader('index.php');
    if (array_key_exists("uid",$req))
        CreateOrEditAPIKey($req['kid'],$req['uid']);
    else
        CreateOrEditAPIKey($req['kid'],0);
}
else
{
    PrintHeader('index.php',$kk);
    PrintAPIKeys();
}