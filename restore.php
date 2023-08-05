<?php

require_once "functions.php";

$user_id_map = array();
$endpoint_id_map = array();
$folder_id_map = array();
$oid_map = array();
$document_id_map = array();
$message_id_map = array();


function Swap($t1,&$rows,$col)
{
    foreach($rows as &$row)
    {
        foreach($t1 as $t)
        {
            if ($t[0] == $row[$col])
            {
                $row[$col] = $t[1];
                break;
            }
        }
    }
}


function GenericInsertTableAll($n,$row,$killid = 0,$b64 = array())
{
    global $lastRowID;
    $x = sprintf("INSERT INTO %s (",$n);
    foreach($row as $k=>$r)
    {
        if (is_numeric(($k)))
            continue;
        if ($k == "ID" && $killid == 1)
            continue;
        $x .= $k;
        $x .= ',';
    }
    $x = substr($x, 0, -1);
    $x .= ') VALUES (';
    $aa = array();
    foreach($row as $k=>$r)
    {
        if (is_numeric(($k)))
            continue;
        if ($k == "ID" && $killid == 1)
            continue;
        $x .= '?';
        $x .= ',';
        if (in_array($k,$b64))
            $aa[] = base64_decode($r);
        else
            $aa[] = $r;
    }
    $x = substr($x, 0, -1);
    $x .= ')';
    $lastRowID = 0;
    QQ($x,$aa);
    return $lastRowID;
}


function RestoreAttachment($j,$mid = 0)
{
    global $lastRowID;
    if ($mid == 0)
        $mid = $j['MID'];
    $j['MID']  = $mid;
    GenericInsertTableAll("ATTACHMENTS",$j,1,array("DATA"));
    $j['MID'] = $mid;
    $j['ID'] = $lastRowID;
    if (!$lastRowID)
        return null;
    return $j;
}

function RestoreMessage($j,$uid = 0,$did = 0) 
{
    global $lastRowID;
    global $message_id_map;
    if ($uid == 0)
        $uid = $j['m']['UID'];
    if ($did == 0)
        $did = $j['m']['DID'];

    $oldid = $j['m']['ID'];
    $j['m']['UID'] = $uid;
    $j['m']['DID'] = $did;
    GenericInsertTableAll("MESSAGES",$j['m'],1,array("SIGNEDPDF"));
    if (!$lastRowID)
        return null;
    $message_id_map[] =  array($oldid,$lastRowID);
    
    $j['m']['DID'] = $did;
    $j['m']['UID'] = $uid;
    $j['m']['ID'] = $lastRowID;
    if (!$lastRowID)
        return null;

    foreach($j['attachments'] as $attachment)
    {
        RestoreAttachment($attachment,$j['m']['ID']);
        nop();
    }
    return $j;
}


function RestoreDocument($j,$uid = 0,$eid = 0,$fid = 0) 
{
    global $lastRowID;
    global $document_id_map;

    if ($uid == 0)
        $uid = $j['d']['UID'];
    if ($eid == 0)
        $eid = $j['d']['EID'];
    if ($fid == 0)
        $fid = $j['d']['FID'];

    $oldid = $j['d']['ID'];
    $j['d']['FID'] = $fid;
    $j['d']['EID'] = $eid;
    $j['d']['UID'] = $uid;
    GenericInsertTableAll("DOCUMENTS",$j['d'],1);
    if (!$lastRowID)
        return null;
    $document_id_map[] =  array($oldid,$lastRowID);
    $j['d']['EID'] = $eid;
    $j['d']['UID'] = $uid;
    $j['d']['ID'] = $lastRowID;
    foreach($j['messages'] as $message)
    {
        RestoreMessage($message,$uid,$j['d']['ID']);
    }
    return $j;
}

function RestoreFolder($j,$eid)
{
    global $lastRowID;
    global $folder_id_map;
    global $user_id_map;


    $j['f']['EID'] = $eid;
    $oldid = $j['f']['ID'];
    $oldp = $j['f']['PARENT'];
    $j['f']['PARENT'] = 0;
    GenericInsertTableAll("FOLDERS",$j['f'],1);
    if (!$lastRowID)
        return null;
    $folder_id_map[] =  array($oldid,$lastRowID,$oldp);

    $j['f']['EID'] = $eid;
    $j['f']['ID'] = $lastRowID;

    foreach($j['documents'] as $document)
    {
        RestoreDocument($document,0,$eid,$j['f']['ID']);
        nop();
    }
    return $j;
}


function RestoreEndpoint($j,$oid)
{
    global $lastRowID;
    global $endpoint_id_map;
    global $folder_id_map;

    $j['e']['OID'] = $oid;
    $oldid = $j['e']['ID'];
    $oldp = $j['e']['PARENT'];
    $j['e']['PARENT'] = 0;
    GenericInsertTableAll("ENDPOINTS",$j['e'],1);
    if (!$lastRowID)
        return null;
    $eid = $lastRowID;
    $j['e']['ID'] = $eid;
    $endpoint_id_map[] =  array($oldid,$eid,$oldp);

    // Folders
    foreach($j['folders'] as $folder)
    {
        RestoreFolder($folder,$eid);
    }

    // Folder resolve
    foreach($folder_id_map as $epm)
    {
        $refer_parent = $epm[2];
        foreach($folder_id_map as $epm2)
        {
            if ($refer_parent == $epm2[0])
                {
                    QQ("UPDATE FOLDERS SET PARENT = ? WHERE ID = ?",array($epm2[1],$epm[1]));
                    break;
                }
        }
    }

    
    // Roles
    Swap($endpoint_id_map,$j['roles'],"EID");
    foreach($j['roles'] as $role)
    {
        GenericInsertTableAll("ROLES",$role,1);
        $role['ID'] = $lastRowID;
    }
    
    // AB
    Swap($endpoint_id_map,$j['ab'],"EID");
    foreach($j['ab'] as $ab)
    {
        GenericInsertTableAll("ADDRESSBOOK",$ab,1);
        $ab['ID'] = $lastRowID;
    }
    return $j;
}

function RestoreUsers($j)
{
    global $lastRowID;
    global $user_id_map;
    foreach($j['users'] as $u)
    {
        $a = $u['ID'];
        GenericInsertTableAll("USERS",$u,1);
        $user_id_map[] = array($a,$lastRowID);
        $u['ID'] = $lastRowID;
    }
}

function RestoreAPI($j)
{
    global $lastRowID;
    global $user_id_map;
    Swap($user_id_map,$j['apikeys'],"UID");
    foreach($j['apikeys'] as $u)
    {
        GenericInsertTableAll("APIKEYS",$u,1);
    }
}

function RestoreGlobalRoles($j)
{
    global $user_id_map;
    global $lastRowID;
    Swap($user_id_map,$j['roles'],"UID");

    foreach($j['roles'] as $role)
    {
        GenericInsertTableAll("ROLES",$role,1);
        $role['ID'] = $lastRowID;
    }

}


function RestoreOrganization($j)
{
    global $lastRowID;
    global $oid_map;
    global $endpoint_id_map;
    $lastRowID = 0;
    $old = $j['o']['ID'];
    GenericInsertTableAll("ORGANIZATIONS",$j['o'],1);
    if (!$lastRowID)
        return null;
    $oid = $lastRowID;
    $oid_map[] = array($old,$lastRowID);
    $j['o']['ID'] = $oid;

    foreach($j['endpoints'] as $ep)
    {
        RestoreEndpoint($ep,$oid);
        nop();
    }

    // Parent resolve
    foreach($endpoint_id_map as $epm)
    {
        $refer_parent = $epm[2];
        foreach($endpoint_id_map as $epm2)
        {
            if ($refer_parent == $epm2[0])
                {
                    QQ("UPDATE ENDPOINTS SET PARENT = ? WHERE ID = ?",array($epm2[1],$epm[1]));
                    break;
                }
        }
    }

    // Roles
    Swap($oid_map,$j['roles'],"OID");
    foreach($j['roles'] as $role)
    {
        GenericInsertTableAll("ROLES",$role,1);
        $role['ID'] = $lastRowID;
    }

    // AB
    Swap($oid_map,$j['ab'],"OID");
    foreach($j['ab'] as $ab)
    {
        GenericInsertTableAll("ADDRESSBOOK",$ab,1);
        $ab['ID'] = $lastRowID;
    }
    return $j;
}

if (array_key_exists("file_x",$_FILES) && strlen($_FILES['file_x']['tmp_name']))
    {   
        $c = file_get_contents($_FILES['file_x']['tmp_name']);
        $j = unserialize($c);
        if (!$j)
            die;
        if (!$u->superadmin)
            die;
        if (array_key_exists("organizations",$j))
        {
            echo 'Restoring full backup<br><hr>';
            RestoreUsers($j);
            RestoreGlobalRoles($j);
            RestoreAPI($j);
            foreach($j['organizations'] as $op)
            {
             RestoreOrganization($op);
             nop();
            }
    
            printr($j);
          }
        die;
    }



if (array_key_exists("file_o",$_FILES) && strlen($_FILES['file_o']['tmp_name']))
    {   
        $c = file_get_contents($_FILES['file_o']['tmp_name']);
        $j = unserialize($c);
        if (!$j)
            die;

        if (array_key_exists("endpoints",$j))
        {
            echo 'Restoring an organization<br><hr>';
            if (!$u->superadmin)
                die;
            RestoreOrganization($j);
        }
        redirect("eggr.php");
        die;
    }


if (array_key_exists("file_e",$_FILES) && array_key_exists("oid",$_POST) && strlen($_FILES['file_e']['tmp_name']))
    {   
        $c = file_get_contents($_FILES['file_e']['tmp_name']);
        $j = unserialize($c);
        if (!$j)
            die;

        if (array_key_exists("folders",$j))
        {
            echo 'Restoring an endpoint<br><hr>';
            if (UserAccessOID($_POST['oid'],$u->uid) != 2)
                die;
            RestoreEndpoint($j,$_POST['oid']);
        }
        redirect("eggr.php");
        die;
    }

if (array_key_exists("file_f",$_FILES) && array_key_exists("eid",$_POST) && strlen($_FILES['file_f']['tmp_name']))
    {   
        $c = file_get_contents($_FILES['file_f']['tmp_name']);
        $j = unserialize($c);
        if (!$j)
            die;

        if (array_key_exists("documents",$j))
        {
            echo 'Restoring a folder<br><hr>';
            if (UserAccessEP($_POST['eid'],$u->uid) != 2)
                die;
            RestoreFolder($j,$_POST['eid']);
        }
        redirect("eggr.php");
        die;
    }

require_once "output.php";
PrintHeader('index.php');

?>

<form  action="restore.php" method="POST" enctype="multipart/form-data">
    <br><br>

    
    <?php
if ($u->superadmin)
{
?>
    <article class="panel is-primary">
    <p class="panel-heading">
    Πληρες Restore
  </p>

    <div style="margin: 20px;">
    Επιλογή TXT αρχείου:<br><br>
    <input type="file" name="file_x" id="file_x" accept=".txt">
    <br><br>
    </div>
    </article>

    <article class="panel is-primary">
    <p class="panel-heading">
    Aνάκτηση Φορέα:
  </p>

    <div style="margin: 20px;">
    Επιλογή TXT αρχείου:<br><br>
    <input type="file" name="file_o" id="file_o" accept=".txt">
    <br><br>
    </div>
    </article>
<?php
}
?>

    <article class="panel is-primary">
    <p class="panel-heading">
    Aνάκτηση Endpoint:
  </p>

    <div style="margin: 20px;">
    Μέσα στον φορέα:<br><br>
    <?php
        echo PickOrganization("oid",array(),0,$u->uid,0,0,0,2);
    ?>
    <br><br>
    Επιλογή TXT αρχείου:<br><br>
    <input type="file" name="file_e" id="file_e" accept=".txt">
    <br><br>
    </div>
    </article>


    <article class="panel is-primary">
    <p class="panel-heading">
    Aνάκτηση φακέλου:
  </p>

    <div style="margin: 20px;">
    Μέσα στο endpoint:<br><br>
    <?php
        echo PickEP("eid",array(),0,$u->uid,0,0,0,2);
    ?>
    <br><br>
    Επιλογή TXT αρχείου:<br><br>
    <input type="file" name="file_f" id="file_f" accept=".txt">
    <br><br>
    </div>
    </article>

    <br><br>
    <button class="autobutton button is-success">Υποβολή</button>
</form>
<button href="index.php" class="autobutton button is-danger">Πίσω</button>    