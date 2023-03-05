<?php

require_once "functions.php";



function RestoreAttachment($j,$mid = 0)
{
    global $lastRowID;
    if ($mid == 0)
        $mid = $j['MID'];
    QQ("INSERT INTO ATTACHMENTS (MID,NAME,TYPE,DESC,DATA) VALUES(?,?,?,?,?)",array($mid,$j['NAME'],$j['TYPE'],$j['DESC'],base64_decode($j['DATA'])));
    $j['MID'] = $mid;
    $j['ID'] = $lastRowID;
    if (!$lastRowID)
        return null;
    return $j;
}

function RestoreMessage($j,$uid = 0,$did = 0) 
{
    global $lastRowID;
    if ($uid == 0)
        $uid = $j['m']['UID'];
    if ($did == 0)
        $did = $j['m']['DID'];
    QQ("INSERT INTO MESSAGES (UID,DID,MSG,DATE,INFO) VALUES(?,?,?,?,?)",array($uid,$did,$j['m']['MSG'],$j['m']['DATE'],$j['m']['INFO'])); 
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
    if ($uid == 0)
        $uid = $j['d']['UID'];
    if ($eid == 0)
        $eid = $j['d']['EID'];
    if ($fid == 0)
        $fid = $j['d']['FID'];
    QQ("INSERT INTO DOCUMENTS (UID,EID,TOPIC,FID,CLASSIFIED,READSTATE,PROT,RECPX,RECPY,RECPZ,KOINX,KOINY,KOINZ,BCCX,BCCY,BCCZ,TYPE,CLSID,ESWX) VALUES(
        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
    )",array($uid,$eid,$j['d']['TOPIC'],$fid,$j['d']['CLASSIFIED'],$j['d']['READSTATE'],$j['d']['PROT'],$j['d']['RECPX'],$j['d']['RECPY'],$j['d']['RECPZ'],$j['d']['KOINX'],$j['d']['KOINY'],$j['d']['KOINZ'],$j['d']['BCCX'],$j['d']['BCCY'],$j['d']['BCCZ'],$j['d']['TYPE'],$j['d']['CLSID'],$j['d']['ESWX'])); 
    if (!$lastRowID)
        return null;
    $j['d']['EID'] = $eid;
    $j['d']['UID'] = $uid;
    $j['d']['ID'] = $lastRowID;

    foreach($j['messages'] as $message)
    {
        RestoreMessage($message,$uid,$j['d']['ID']);
        nop();
    }
    return $j;
}

function RestoreFolder($j,$eid = 0)
{
    global $lastRowID;
    if ($eid == 0)
        $eid = $j['f']['EID'];
    $j['f']['SPECIALID'] = 0; // Always non special ID

    QQ("INSERT INTO FOLDERS (EID,SPECIALID,NAME,PARENT,CLASSIFIED) VALUES (?,?,?,?,?)",array(
        $eid,$j['f']['SPECIALID'],$j['f']['NAME'],$j['f']['PARENT'],$j['f']['CLASSIFIED']
    ));
    if (!$lastRowID)
        return null;
    $j['f']['EID'] = $eid;
    $j['f']['ID'] = $lastRowID;
    foreach($j['documents'] as $document)
    {
        RestoreDocument($document,0,$eid,$j['f']['ID']);
        nop();
    }
    return $j;
}

function RestoreEndpoint($j,$oid = 0)
{
    global $lastRowID;
    if ($oid == 0)
        $oid = $j['e']['OID'];

    // parent is always 0
    $j['e']['PARENT'] = 0;

    QQ("INSERT INTO ENDPOINTS (OID,NAME,PARENT,EMAIL,T0,T1,T2,T3,T4,T5,T6,T7,T8,T9,A1,A2,A3,TEL1,TEL2,TEL3) VALUES (
        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
    )",array(
        $oid,$j['e']['NAME'],$j['e']['PARENT'],$j['e']['EMAIL'],
        $j['e']['T0'],$j['e']['T1'],$j['e']['T2'],$j['e']['T3'],$j['e']['T4'],$j['e']['T5'],$j['e']['T6'],$j['e']['T7'],$j['e']['T8'],$j['e']['T9'],
        $j['e']['A1'],$j['e']['A2'],$j['e']['A3'],$j['e']['TEL1'],$j['e']['TEL2'],$j['e']['TEL3']
    ));
    if (!$lastRowID)
        return null;
    $eid = $lastRowID;
    $j['e']['ID'] = $eid;

    foreach($j['folders'] as $folder)
    {
        RestoreFolder($folder,$eid);
        nop();
    }
    foreach($j['roles'] as $role)
    {
        QQ("INSERT INTO ROLES (UID,ROLEID,OID,EID) VALUES(?,?,?,?)",array(
            $role['UID'],$role['ROLEID'],$oid,$eid
        ));
    }
    foreach($j['ab'] as $ab)
    {
        QQ("INSERT INTO ADDRESSBOOK (OID,EID,SHDE,CLASSIFIED,PARENT,LASTNAME,FIRSTNAME,TITLE,EMAIL,DATA) VALUES(?,?,?,?,?,?,?,?,?,?)",array(
            $oid,$eid,$ab['SHDE'],$ab['CLASSIFIED'],$ab['PARENT'],$ab['LASTNAME'],$ab['FIRSTNAME'],$ab['TITLE'],$ab['EMAIL'],$ab['DATA']
        ));
    }
    return $j;
}

function RestoreOrganization($j)
{
    global $lastRowID;
    QQ("INSERT INTO ORGANIZATIONS (NAME) VALUES (?)",array(
        $j['o']['NAME']
    ));
    if (!$lastRowID)
        return null;
    $oid = $lastRowID;
    $j['o']['ID'] = $oid;


    foreach($j['endpoints'] as $ep)
        {
         RestoreEndpoint($ep,$oid);
         nop();
        }

    foreach($j['roles'] as $role)
    {
        QQ("INSERT INTO ROLES (UID,ROLEID,OID) VALUES(?,?,?)",array(
            $role['UID'],$role['ROLEID'],$oid
        ));
    }
    foreach($j['ab'] as $ab)
    {
        QQ("INSERT INTO ADDRESSBOOK (OID,SHDE,CLASSIFIED,PARENT,LASTNAME,FIRSTNAME,TITLE,EMAIL,DATA) VALUES(?,?,?,?,?,?,?,?,?)",array(
            $oid,$ab['SHDE'],$ab['CLASSIFIED'],$ab['PARENT'],$ab['LASTNAME'],$ab['FIRSTNAME'],$ab['TITLE'],$ab['EMAIL'],$ab['DATA']
        ));
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