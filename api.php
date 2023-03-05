<?php

require_once "functions.php";

header("Content-Type: application/json");
$data = array("status" => "-1");

function Inv($msg = "")
{
    global $data;
    $data['message'] = "No API key or invalid key or invalid function.";
    if (strlen($msg))
        $data['message'] = $msg;
    echo json_encode($data);
    die;
}

if (!array_key_exists("key",$_POST))
    Inv();

$r = QQ("SELECT * FROM APIKEYS WHERE T1 = ?",array($_POST['key']))->fetchArray();
if (!$r)
    Inv();

$ur = QQ("SELECT * FROM USERS WHERE ID = ?",array($r['UID']))->fetchArray();
if (!$ur)
    Inv();

if (!array_key_exists("f",$_POST))
    Inv();
$f = $_POST['f'];

// Build U in question
$u = new U;
$u->uid = $ur['ID'];
$u->username = $ur['USERNAME'];
$u->firstname = $ur['FIRSTNAME'];
$u->lastname = $ur['LASTNAME'];
$u->title = $ur['TITLE'];
if ($u->username == $superadminuid)
    $u->superadmin = 1;
$q1 = QQ("SELECT * FROM ROLES WHERE UID = ? AND ROLEID = ?",array($u->uid,ROLE_EPADMIN));
while ($r1 = $q1->fetchArray())
    $u->epadmin[] = $r1['EID'];            
$q1 = QQ("SELECT * FROM ROLES WHERE UID = ? AND ROLEID = ?",array($u->uid,ROLE_FADMIN));
while ($r1 = $q1->fetchArray())
    $u->fadmin[] = $r1['OID'];            

// Test
if ($f == "test")
{
    $data['status'] = "0";
    $data['message'] = "Test Succeeded.";
    echo json_encode($data);
    die;
}

// f: create, t: topic, eid: endpoint, oid: organization, fid: folder, info: info, msg: message


// f: receipients, did: document ID

