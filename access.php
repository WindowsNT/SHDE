<?php


function DRow($id,$decrypt = 0)
{
    $r1 = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($id))->fetchArray();
    if ($decrypt)
        DocumentDecrypt($r1);
    return $r1;  
}
function MRow($id,$decrypt = 0)
{
    $r1 = QQ("SELECT * FROM MESSAGES WHERE ID = ?",array($id))->fetchArray();
    if ($decrypt)
        DocumentDecrypt($r1);
    return $r1;
}

function CRow($id,$decrypt = 0)
{
    $r1 = QQ("SELECT * FROM COMMENTS WHERE ID = ?",array($id))->fetchArray();
    if ($decrypt)
        DocumentDecrypt($r1);
    return $r1;
}


function OIDToken($oid)
{
    $loge = sprintf("shde_login_%s",$oid);
    if (!array_key_exists($loge,$_SESSION))
        return "";
    if (!array_key_exists("AccessToken",$_SESSION[$loge]))
        return "";
    return $_SESSION[$loge]["AccessToken"];
}

function UserAccessOID($oid,$uid)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
        return 2;
    $r = QQ("SELECT * FROM ROLES WHERE UID = ? AND OID = ? AND ROLEID = ? AND (EID IS NULL OR EID = 0)",array($uid,$oid,ROLE_FADMIN))->fetchArray();
    if ($r)
        return 2;

    // eps under it
    $q5 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($oid));
    while($r5 = $q5->fetchArray())
    {
        if (UserAccessEP($r5['ID'],$uid,1))
            return 1;
    }

    // top level user
    $r = QQ("SELECT * FROM ROLES WHERE UID = ? AND OID = ? AND ROLEID = ?",array($uid,$oid,ROLE_USER))->fetchArray();
    if ($r)
        return 1;
    
    return 0;
}



function UserAccessUser($wid,$uid)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
            return 2;
    if ($wid == $uid)
        return 2;
    $q = QQ("SELECT * FROM ROLES WHERE UID = ?",array($wid));
    while($r = $q->fetchArray())
    {
        $a = UserAccessOID($r['OID'],$uid);
        if ($a)
            return 1;
    }
    return 0;
}

  
  
function UserAccessAB($e,$uid)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
        return 2;
    $r = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($e))->fetchArray();
    if (!$r)
        return 0;
    $q = QQ("SELECT * FROM ROLES WHERE UID = ?",array($uid));
    $l = 0;
    while ($r2 = $q->fetchArray())
    {
        if ($r2['ROLEID'] == ROLE_FADMIN && $r2['OID'] == $r['OID'])
            $l = 2;
        if ($r2['ROLEID'] == ROLE_EPADMIN && $r2['EID'] == $r['EID'])
            $l = 2;
        if ($r2['ROLEID'] == ROLE_EDITOR && ($r2['EID'] == $r['EID'] || $r2['OID'] == $r['OID']) && $l == 0)
            $l = 1;
        if ($r2['ROLEID'] == ROLE_USER && ($r2['EID'] == $r['EID'] || $r2['OID'] == $r['OID']) && $l == 0)
            $l = 1;
        if ($r2['ROLEID'] == ROLE_SIGNER0 && ($r2['EID'] == $r['EID'] || $r2['OID'] == $r['OID']) && $l == 0)
            $l = 1;
    }
    return $l;
}


function ShdeUrl($oid)
{
    $r = QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($oid))->fetchArray();
    if ($r && $r['SHDEPRODUCTION'] == 1)
        return 'https://sddd.mindigital-shde.gr/api/v1';
    return 'https://sdddsp.mindigital-shde.gr/api/v1';    
}


function UserAccessEP($eip,$uid,$nooid = 0)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
        return 2;
    $q = QQ("SELECT * FROM ROLES WHERE UID = ? AND EID = ?",array($uid,$eip));
    $l = 0;
    while ($r = $q->fetchArray())
    {
        if ($r['ROLEID'] == ROLE_EPADMIN && $l < 2)
            $l = 2;
        if ($r['ROLEID'] == ROLE_SIGNER0 && $l < 1)
            $l = 2;
        if ($r['ROLEID'] == ROLE_EDITOR && $l < 1)
            $l = 1;
        if ($r['ROLEID'] == ROLE_USER && $l < 1)
            $l = 1;
    }

    $epr = EPRow($eip);
    if ($nooid == 0 && $epr)
    {
        $ol = UserAccessOID($epr['OID'],$uid);
        if ($ol == 2)
            return $ol;
    }

    // Check if parent
    $er = EPRow($eip);
    if ($er['PARENT'])
    {
        $l2 = UserAccessEP($er['PARENT'],$uid,$nooid);
        if ($l2 > $l)
            $l = $l2;
    }
    return $l;
}

function UserAccessLocker($lid,$uid)
{
    $q1 = QQ("SELECT * FROM LOCKERS WHERE ID = ?",array($lid))->fetchArray();
    if (!$q1)  
        return 0;

    $a0 = 0;
    $a1 = 0;
    $a2 = 0;
    $q2 = QQ("SELECT * FROM USERSINLOCKER WHERE LID = ? AND UID = ?",array($lid,$uid))->fetchArray();
    if ($q2)
        $a0 = 1;

    if ($q1['EID'])
        $a1 = UserAccessEP($q1['EID'],$uid);
    if ($q1['OID'])
        $a2 = UserAccessOID($q1['EID'],$uid);

    return max(array($a0,$a1,$a2));
}



function UserAccessDocument($did,$uid)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
        return 2;
    $r = DRow($did);
    if (!$r)
        return 0;

    $ur = UserRow($uid);
    if ($ur['CLASSIFIED'] < $r['CLASSIFIED'])
        return 0;

    if ($r['UID'] == $uid)
        return 2; // write access

    return  UserAccessEP($r['EID'],$uid);
}


function CanSend($did,$uid)
{
        // Roles of the user
        global $u;
        if ($u->superadmin && $u->uid == $uid)
            return 1;

        $doc  = DRow($did);
        if (!$doc)
            return false;
        $eid = $doc['EID'];
        $drow = EPRow($eid);
        if (!$drow)
            return false;
        $oid = $drow['OID'];
        $orow = FRow($oid);
        if (!$orow)
            return false;

        $qr1 = QQ("SELECT * FROM ROLES WHERE UID = ?",array($uid));
        while($rr1 = $qr1->fetchArray())
        {
/*
            if ($rr1['ROLEID'] == ROLE_FADMIN && $rr1['OID'] == $oid)
                return 1;
            if ($rr1['ROLEID'] == ROLE_EPADMIN && $rr1['EID'] == $eid)
                return 1;
*/                
            if ($rr1['ROLEID'] == ROLE_SIGNER0 && $rr1['EID'] == $eid)
                return 1;
        }
    
        return 0;
}

function UserAccessFolder($fid,$uid)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
        return 2;

    $r = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($fid))->fetchArray();
    if (!$r)
        return 0;

    $ur = UserRow($uid);
    if ($ur['CLASSIFIED'] < $r['CLASSIFIED'])
        return 0;

    if ($r['LID'])
        return UserAccessLocker($r['LID'],$uid);
    return UserAccessEP($r['EID'],$uid);
}


function UserAccessMessage($mid,$uid)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
        return 2;

    $r = MRow($mid,0);
    if (!$r)
        return 0;
    if ($r['UID'] == $uid)
        return 2; // write access
    return UserAccessDocument($r['DID'],$uid);
}


function UserAccessComment($cid,$uid)
{
    global $u;
    if ($u->superadmin && $u->uid == $uid)
        return 2;

    $r = CommentRow($cid,0);
    if (!$r)
        return 0;
    if ($r['UID'] == $uid)
        return 2; // write access
    if (UserAccessDocument($r['DID'],$uid))
        return 1;
    return 0;
}

  
function CanSign($did,$uid)
{
    $doc = DRow($did,1);
    if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
        return -1;
    $eid = $doc['EID'];
    $rl = QQ("SELECT * FROM ROLES WHERE ROLEID = ? AND UID = ? AND EID = ?",array(ROLE_SIGNER0,$uid,$eid))->fetchArray();
    if (!$rl)
        return -2;
    if ($doc['TYPE'] != 0)
        return -3; // email
    $msg = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($doc['ID']))->fetchArray();
    if (!$msg)
        return -4;
    if ($msg['SIGNEDPDF'] && strlen($msg['SIGNEDPDF']) > 5)
        return -5;
    
    return 0;
}