<?php
 
 require_once "functions.php";
if (!$u)
    diez();

//    phpinfo();
if (array_key_exists("delete",$req) && $u->superadmin)
{
    if (array_key_exists("force",$req))
    {
        QQ("BEGIN TRANSACTION");
        DeleteWholeOrganization($req['delete']);
        QQ("COMMIT");
        QQ("VACUUM");
        redirect("organizations.php");
        die;
    }

    require_once "output.php";
    echo '<div class="content" style="margin:20px;">';
    echo 'Διαγραφή Φορέα';

    $doccount = 0;
    $ecount = 0;
    $fcount = 0;

    $q2 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($req['delete']));
    while($r2 = $q2->fetchArray())
    {
        $ecount++;
        $fcount += CountDB("FOLDERS WHERE EID = ?",array($r2['ID']));        
        $doccount += CountDB("DOCUMENTS WHERE EID = ?",array($r2['ID']));
    }
    $abcount = CountDB("ADDRESSBOOK WHERE OID = ?",array($req['delete']));

    if ($fcount || $doccount || $ecount || $abcount) 
        printf("<br><b>Η διαγραφή του φορέα θα διαγράψει οριστικά: %s endpoints, %s φακέλους με συνολικά %s έγγραφα καθώς και %s εγγραφές στο βιβλίο διευθύνσεων!</b>",$ecount,$fcount,$doccount,$abcount);

    printf('<br><br><button href="organizations.php?delete=%s&force=1" class="button autobutton is-danger">Επιβεβαίωση Διαγραφής</button><br>',$req['delete']);
    printf('<br><hr><button href="organizations.php" class="button autobutton is-success">Πίσω</button>');

    die;
}


if (array_key_exists("c",$_POST))
{
    $r = QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($_POST['c']))->fetchArray();
    if (!$r)
    {
        // Create
        if ($u->superadmin)
        {
            QQ("INSERT INTO ORGANIZATIONS (NAME,NAMEEN,SHDECODE,SHDECLIENT,SHDESECRET,SHDECLIENT2,SHDESECRET2,SHDEPRODUCTION) VALUES (?,?,?,?,?,?,?,?)",array($_POST['name'],$_POST['nameen'],$_POST['code'],$_POST['client'],$_POST['secret'],$_POST['client2'],$_POST['secret2'],$_POST['prod']));
        }
    }
    else
    {
        // Edit
        QQ("UPDATE ORGANIZATIONS SET NAME = ?,NAMEEN = ?,SHDECODE = ?,SHDECLIENT = ?,SHDESECRET = ?,SHDECLIENT2 = ?,SHDESECRET2 = ?,SHDEPRODUCTION = ? WHERE ID = ?",array($_POST['name'],$_POST['nameen'],$_POST['code'],$_POST['client'],$_POST['secret'],$_POST['client2'],$_POST['secret2'],$_POST['prod'],$_POST['c']));
        $loge = sprintf("shde_login_%s",$r['ID']);
        unset($_SESSION[$loge]);
        }
    
    redirect("organizations.php");
    die;
}

function CreateOrEditOrganization($oid)
{
    global $u;
    if (UserAccessOID($oid,$u->uid) != 2)
        diez();

        $r = QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($oid))->fetchArray();
    // Has access ?
    if (!$r)
        $r = array("ID" => 0, "NAME" => "","NAMEEN" => "","SHDECODE" => "","SHDECLIENT" => "","SHDESECRET" => "","SHDECLIENT2" => "","SHDESECRET2" => "","SHDEPRODUCTION" => 0);

    ?>
    <form method="POST" action="organizations.php" autocomplete="new-password">
    <input type="hidden" name="c" value="<?= $r['ID'] ?>" />
    Όνομα: <br>
    <input type="text" class="input" name="name" value="<?= $r['NAME']?>" required/>
    <br><br>
    Όνομα στα Αγγλικά: <br>
    <input type="text" class="input" name="nameen" value="<?= $r['NAMEEN']?>" required/>
    <br><br>
    ΚΣΗΔΕ Όνομα Τομέα: <br>
    <input type="text" class="input" name="code" value="<?= $r['SHDECODE']?>" />
    <br><br>
    ΚΣΗΔΕ Δοκιμαστικό Client ID: <br>
    <input type="text" class="input" name="client2" value="<?= $r['SHDECLIENT2']?>" autocomplete="new-password" >
    <br><br>
    ΚΣΗΔΕ Δοκιμαστικό Secret: <br>
    <input type="password" class="input" name="secret2" value="<?= $r['SHDESECRET2']?>"  autocomplete="new-password" >
    <br><br>
    ΚΣΗΔΕ Παραγωγικό Client ID: <br>
    <input type="text" class="input" name="client" value="<?= $r['SHDECLIENT']?>" autocomplete="new-password" >
    <br><br>
    ΚΣΗΔΕ Παραγωγικό Secret: <br>
    <input type="password" class="input" name="secret" value="<?= $r['SHDESECRET']?>"  autocomplete="new-password" >
    <br><br>
    Αν δεν βάλετε το Secret θα σας ζητήται κάθε φορά που απαιτείται login στο ΚΣΗΔΕ.<br><br>
    Χρήση ΚΣΗΔΕ:<br>
    <select name="prod" class="input">
        <option value="0" <?= $r['SHDEPRODUCTION'] == 0 ? "selected" : "" ?>>Δοκιμαστική</option>
        <option value="1" <?= $r['SHDEPRODUCTION'] == 1 ? "selected" : "" ?>>Παραγωγική</option>
    </select>
    <br><br>
    <button class="button is-primary">Υποβολή</button>
    </form>
    <button href="organizations.php" class="autobutton button is-danger">Άκυρο</button>
    <?php
}

function PrintOrganzations()
{
    global $u;
    $q = QQ("SELECT * FROM ORGANIZATIONS");
    ?>
    <script>
        function arch(oid,sta)
        {
            if (confirm("Σίγουρα;"))
            {
                block();
                window.location = 'shdeincoming.php?oid=' + oid + '&archive=' + sta;
            }
        }
        </script>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        <th class="all">Όνομα</th>
        <th class="all">ΚΣΗΔΕ</th>
        <th  class="all">Επιλογές</th>
    </thead>
    <tbody>
    <?php
    while($r = $q->fetchArray())
    {
        $a = UserAccessOID($r['ID'],$u->uid);
        if ($a == 0)
            continue;
        printf('<tr>');

        printf('<td>%s</td>',$r['ID']);
        printf('<td>%s<br>%s</td>',$r['NAME'],$r['NAMEEN']);
        printf('<td>Δοκιμαστικό: %s &mdash; %s [%s]',$r['SHDECODE'],$r['SHDECLIENT2'],$r['SHDEPRODUCTION'] == 0 ? "<b>Τρέχον</b>": "");
        printf('<br>Παραγωγικό: %s &mdash; %s [%s]</td>',$r['SHDECODE'],$r['SHDECLIENT'],$r['SHDEPRODUCTION'] == 1 ? "<b>Τρέχον</b>": "");

        if ($a == 2)
            printf('<td><a href="organizations.php?oid=%s">Επεξεργασία</a> &mdash; <a href="shdeputorgchart.php?oid=%s">Upload OrgChart</a> &mdash; <a href="endpoints.php?oid=%s">Endpoints</a> &mdash; <a href="lockers.php?oid=%s">Θυρίδες</a> &mdash;  <a href="rules.php?oid=%s">Rules</a> &mdash; <a href="restrictions.php?oid=%s">Περιορισμοί</a>  &mdash; <a href="javascript:arch(%s,1);">Λήψη Αρχείου ΚΣΗΔΕ</a> &mdash; <a href="javascript:arch(%s,2);">Λήψη Αρχείου Απεσταλμένων ΚΣΗΔΕ</a> ',$r['ID'],$r['ID'],$r['ID'],$r['ID'],$r['ID'],$r['ID'],$r['ID'],$r['ID']);
        else
            printf('<td><a href="endpoints.php?oid=%s">Endpoints</a> ',$r['ID']);
        if ($u->superadmin)
            printf(' &mdash; <a href="organizations.php?delete=%s">Διαγραφή</a></td>',$r['ID']);
        printf('</td>');
        printf('</tr>');
    }
    ?>
    </tbody>
    </table>
    <?php
}

function OrgTree($uid,$ar = 0,$oidr = null,$eidr = null,$fidr = null,$nest = 0)
{
    $fis = '';
    if ($oidr == null)
    {
        $fis .= sprintf('<div class="tf-tree"><ul><li><span class="tf-nc">Φορείς</span><ul>');
        $q1 = QQ("SELECT * FROM ORGANIZATIONS ORDER BY NAME ASC");
        while($r1 = $q1->fetchArray())
        {
            $a1 = UserAccessOID($r1['ID'],$uid);
            if ($a1 < $ar)
                continue;
            $fis .= sprintf('<li><span class="tf-nc"><a href="organizations.php?oid=%s">%s</a></span>',$r1['ID'],   $r1['NAME']);
            $fis .= OrgTree($uid,$ar,$r1,null,null,$nest + 1);
            $fis .= '</li>';
        }
        $fis .= '</ul></li></ul>';
    }
    else
    {
        if ($eidr == null)
        {
            $fis .= sprintf('<ul>');
            $q2 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ? AND PARENT = 0 ORDER BY NAME ASC",array($oidr['ID']));
            while($r2 = $q2->fetchArray())
            {
                $a2 = UserAccessEP($r2['ID'],$uid);
                if ($a2 < $ar)
                    continue;
                $fis .= sprintf('<li><span class="tf-nc"><a href="endpoints.php?eid=%s">%s</a></span>',$r2['ID'],$r2['NAME']);



                $fis .= sprintf('<ul>');
                $q4 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ? AND PARENT = ? ORDER BY NAME ASC",array($oidr['ID'],$r2['ID']));
                while($r4 = $q4->fetchArray())
                {
                    $a4 = UserAccessEP($r4['ID'],$uid);
                    if ($a2 < $ar)
                        continue;
                    $fis .= sprintf('<li><span class="tf-nc"><a href="endpoints.php?eid=%s">%s</a></span>',$r4['ID'],$r4['NAME']);
//                    $fis .= OrgTree($uid,$ar,$oidr,$r4,null,$nest + 1);
                    $fis .= '</li>';
                }
                $fis .= sprintf('</ul>');
    
//                $fis .= OrgTree($uid,$ar,$oidr,$r2,null,$nest + 1);
                $fis .= '</li>';
            }
            $fis .= '</ul>';
        }
        else
        {
            if ($fidr == null)
            {
                $fis .= sprintf('<ul>');
                $q3 = QQ("SELECT * FROM FOLDERS WHERE EID = ? ORDER BY NAME ASC",array($eidr['ID']));
                while($r3 = $q3->fetchArray())
                {
                    $a3 = UserAccessFolder($r3['ID'],$uid);
                    if ($a3 < $ar)
                        continue;
//                    $fis .= sprintf('<li><span class="tf-nc">%s</span>',$r3['NAME']);
  //                  $fis .= OrgTree($uid,$ar,$oidr,$eidr,$r3,$nest + 1);
    //                $fis .= '</li>';
                }
                $fis .= '</ul>';
            }
            }
        }
    return $fis;
}

require_once "output.php";
if (array_key_exists("oid",$req))
{
    echo '<div class="content" style="margin: 20px">';
    CreateOrEditOrganization($req['oid']);
}
else
{

    if ($u->superadmin)
        PrintHeader('index.php','&nbsp; <button class="button is-primary autobutton" href="organizations.php?oid=0">Νέος Φορέας</button> ');
    else
        PrintHeader('index.php');
    PrintOrganzations();
    echo OrgTree($u->uid);
}
?>

</div>
