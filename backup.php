<?php

require_once "functions.php";

/*
    Organizations
    Endpoints
    Folders
    Documents
    Messages
    Attachments
*/

function Array1($array)
{
    for($i = 0 ; ; $i++)
    {
        if (!array_key_exists($i,$array))
            break;
        unset($array[$i]);
    }

    return $array;
}



function BackupAB($oid,$eid)
{
    $b = array();
    if ($eid && $oid)
        $q1 = QQ("SELECT * FROM ADDRESSBOOK WHERE OID = ? AND EID = ?",array($oid,$eid));
    if ($eid && !$oid)
        $q1 = QQ("SELECT * FROM ADDRESSBOOK WHERE EID = ?",array($eid));
    if (!$eid && $oid)
        $q1 = QQ("SELECT * FROM ADDRESSBOOK WHERE OID = ?",array($oid));
    if (!$eid && !$oid)
        $q1 = QQ("SELECT * FROM ADDRESSBOOK");
    while($r1 = $q1->fetchArray())
        $b[$r1['ID']] = $r1;
    return $b;
}


function BackupRoles($oid,$eid)
{
    $b = array();
    if ($eid && $oid)
        $q1 = QQ("SELECT * FROM ROLES WHERE OID = ? AND EID = ?",array($oid,$eid));
    if ($eid && !$oid)
        $q1 = QQ("SELECT * FROM ROLES WHERE EID = ?",array($eid));
    if (!$eid && $oid)
        $q1 = QQ("SELECT * FROM ROLES WHERE OID = ? AND (EID IS NULL OR EID = 0)",array($oid));
    if (!$eid && !$oid)
        $q1 = QQ("SELECT * FROM ROLES WHERE (OID IS NULL OR OID = 0) AND (EID IS NULL OR EID = 0)");
    while($r1 = $q1->fetchArray())
        $b[$r1['ID']] = $r1;
    return $b;
}



function BackupUsers()
{
    $b = array();
    $q1 = QQ("SELECT * FROM USERS");
    while($r1 = $q1->fetchArray())
        $b[$r1['ID']] = $r1;
    return $b;
}

function BackupAPIKeys()
{
    $b = array();
    $q1 = QQ("SELECT * FROM APIKEYS");
    while($r1 = $q1->fetchArray())
        $b[$r1['ID']] = $r1;
    return $b;
}


function BackupAttachment($id)
{
    $ar = QQ("SELECT * FROM ATTACHMENTS WHERE ID = ?",array($id))->fetchArray();
    if (!$ar)
        return null;
    $ar['DATA'] = GetBinary('ATTACHMENTS','DATA',$id);
    $ar['DATA'] = base64_encode($ar['DATA']);
    return Array1($ar);
}




function BackupMessage($id)
{
    $ar = QQ("SELECT * FROM MESSAGES WHERE ID = ?",array($id))->fetchArray();
    if (!$ar)
        return null;
    if ($ar['SIGNEDPDF'] && strlen($ar['SIGNEDPDF']))
        $ar['SIGNEDPDF'] = base64_encode($ar['SIGNEDPDF']);
    $b = array();
    $b['attachments'] = array();
    $q1 = QQ("SELECT * FROM ATTACHMENTS WHERE MID = ?",array($id));
    while($r1 = $q1->fetchArray())
    {
        $att = BackupAttachment($r1['ID']);
        if ($att)
            $b['attachments'][$r1['ID']] = $att;
    }
    $b['m'] = Array1($ar);
    return $b;
}


function BackupDocument($id)
{
    global $u;
    $ar = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($id))->fetchArray();
    if (!$ar)
        return null;
    if ($ar['CLASSIFIED'] > UserRow($u->uid)['CLASSIFIED'])
        return null;
    $b = array();
    $b['messages'] = array();
    $q1 = QQ("SELECT * FROM MESSAGES WHERE DID = ?",array($id));
    while($r1 = $q1->fetchArray())
    {
        $msg = BackupMessage($r1['ID']);
        if ($msg)
            $b['messages'][$r1['ID']] = $msg;
    }
    $b['d'] = Array1($ar);
    return $b;
}



function BackupFolder($id)
{
    global $u;
    $ar = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($id))->fetchArray();
    if (!$ar)
        return null;
    if ($ar['CLASSIFIED'] > UserRow($u->uid)['CLASSIFIED'])
        return null;

    $b = array();
    $b['documents'] = array();
    $q1 = QQ("SELECT * FROM DOCUMENTS WHERE FID = ?",array($id));
    while($r1 = $q1->fetchArray())
    {
        $docr = BackupDocument($r1['ID']);
        if ($docr)
            $b['documents'][$r1['ID']] = $docr;
    }
    $b['f'] = Array1($ar);
    return $b;
}




function BackupEndpoint($id)
{
    $b = array();
    $b['folders'] = array();
    $q1 = QQ("SELECT * FROM FOLDERS WHERE EID = ?",array($id));
    while($r1 = $q1->fetchArray())
    {
        $fold = BackupFolder($r1['ID']);
        if ($fold)
            $b['folders'][$r1['ID']] = $fold;
    }
    $b['ab'] = BackupAB(0,$id);
    $b['roles'] = BackupRoles(0,$id);
    $b['e'] = Array1(QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($id))->fetchArray());
    return $b;
}



function BackupOrganization($id)
{
    $b = array();
    $b['endpoints'] = array();
    $q1 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($id));
    while($r1 = $q1->fetchArray())
    {
        $b['endpoints'][$r1['ID']] = BackupEndpoint($r1['ID']);
    }
    $b['ab'] = BackupAB($id,0);
    $b['roles'] = BackupRoles($id,0);
    $b['o'] = Array1(QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($id))->fetchArray());
    return $b;
}



function BackupEverything()
{
    $b = array();
    $q1 = QQ("SELECT * FROM ORGANIZATIONS",array());
    while($r1 = $q1->fetchArray())
    {
        $b['organizations'][$r1['ID']] = BackupOrganization($r1['ID']);
    }
    $b['ab'] = BackupAB(0,0);
    $b['roles'] = BackupRoles(0,0);
    $b['users'] = BackupUsers(0,0);
    $b['apikeys'] = BackupAPIKeys();
    return $b;
}

function hdr()
{
    header("Content-type: text/plain");
}

if (array_key_exists("vacuum",$req))
{
    QQ("VACUUM");
    redirect("backup.php");
    die;
}


if ($u->superadmin)
{

}



if (array_key_exists("eid",$req) && array_key_exists("oid",$req) && array_key_exists("fid",$req))
{
    if (UserAccessFolder($req['fid'],$u->uid) != 2)
        die;
    $j = serialize(BackupFolder($req['fid']));
    hdr();
    header(sprintf('Content-Disposition: attachment; filename="%s-%s-%s.txt"',$req['oid'],$req['eid'],$req['fid']));
    echo $j;
}
else
if (array_key_exists("eid",$req) && array_key_exists("oid",$req))
{
    if (UserAccessEP($req['eid'],$u->uid) != 2)
        die;
    $j = serialize(BackupEndpoint($req['eid']));
    hdr();
    header(sprintf('Content-Disposition: attachment; filename="%s-%s.txt"',$req['oid'],$req['eid']));
    echo $j;
}
else
if (array_key_exists("oid",$req))
{
    if (UserAccessOid($req['oid'],$u->uid) != 2)
        die;
    $j = serialize(BackupOrganization($req['oid']));
    hdr();
    header(sprintf('Content-Disposition: attachment; filename="%s.txt"',$req['oid']));
    echo $j;
}
else
if (array_key_exists("e",$req) && $u->superadmin)
{
    $ev = BackupEverything();
    $j = serialize($ev);
    hdr();
    header(sprintf('Content-Disposition: attachment; filename="all.txt"'));
    echo $j;
}
else
{
    require_once "output.php";
    PrintHeader('index.php');

    if ($u->superadmin)
        {
            printf('<a href="backup.php?vacuum=1">%s (%s KB)</a> &mdash; ','Vacuum',filesize($dbxx)/1024);
            printf('<a href="backup.php?e=1">%s</a>','Γενικό Backup');
        }

    $q1 = QQ("SELECT * FROM ORGANIZATIONS");
    printf('<div class="content"><ol>');
    while($r1 = $q1->fetchArray())
    {
        $a1 = UserAccessOid($r1['ID'],$u->uid);
        if ($a1 == 0)
            continue; 
        printf('<li>');
        if ($a1 == 2)
            printf('<a href="backup.php?oid=%s">%s</a>',$r1['ID'],$r1['NAME']);
        else
            printf('%s',$r1['NAME']);


        $q2 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($r1['ID']));
        printf('<ol>');
        while($r2 = $q2->fetchArray())
        {
            $a2 = UserAccessEP($r2['ID'],$u->uid);
            if ($a2 == 0)
                continue; 
            printf('<li>');
            if ($a2 == 2)
                printf('<a href="backup.php?oid=%s&eid=%s">%s</a>',$r1['ID'],$r2['ID'],$r2['NAME']);
            else
                printf('%s',$r2['NAME']);
    
            $q3 = QQ("SELECT * FROM FOLDERS WHERE EID = ?",array($r2['ID']));
            printf('<ol>');
            while($r3 = $q3->fetchArray())
            {
                $a3 = UserAccessFolder($r3['ID'],$u->uid);
                if ($a3 == 0)
                    continue; 
                printf('<li>');
                if ($a3 == 2)
                    printf('<a href="backup.php?oid=%s&eid=%s&fid=%s">%s</a>',$r1['ID'],$r2['ID'],$r3['ID'],$r3['NAME']);
                else
                    printf('%s',$r3['NAME']);        
                printf('</li>');
            }
            printf('</ol>');       

            printf('</li>');
        }
        printf('</ol>');       
        printf('</li>');
    }
    printf("</ol></div>");
}

