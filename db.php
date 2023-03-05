<?php

$lastRowID = 0;
$db = null;
$mustprepare = 0;
$mysqli = 0;

// Local tests
if (defined("MDB_PORT"))
    {
        $dbxx = "localhost:".MDB_PORT;
        $login_demo = 1;
    }

function QQZ_SQLite($dbs,$q,$arr = array(),$stmtx = null)
{
    global $lastRowID;
    global $superadmin;

	$stmt = $stmtx;
    if (!$stmt)
        $stmt = $dbs->prepare($q);
    if (!$stmt)
        return null;
    $i = 1;
    foreach($arr as $a)
    {
        $stmt->bindValue($i,$a);
        $i++;
    }
    $a = $stmt->execute();
    $lastRowID = $dbs->lastInsertRowID();
    if ($a === FALSE)
        {
            die("Database busy, please try later.");
        }
    return $a;
}



class msql_wrap
{
    public $rx;

    public function fetchArray()
    {
        if (!$this->rx)
            return null;
        if ($this->rx->num_rows == 0)
            return null;
        return $this->rx->fetch_assoc();
    }
};


function QQZ_MySQL(mysqli $dbs,$q,$arr = array(),$stmt = null)
{
    global $lastRowID;
    if (!is_array($arr)) die("QQZ_MySQL passed not an array.");

    if (!$stmt)
	    $stmt = $dbs->prepare($q);
    if (!$stmt)
        return null;
    $arx = array();
    $bp = "";
    foreach($arr as $a)
        $bp .= "s";

    if (count($arr) > 0)
    {
        $arx [] = &$bp;
        foreach($arr as &$a)
             $arx [] = &$a;
        call_user_func_array (array($stmt,'bind_param'),$arx);
    }

    $stmt->execute();
    $a = $stmt->get_result();
    $lastRowID = $dbs->insert_id;
    $m = new msql_wrap;
    $m->rx = $a;

    return $m;
}


function QQZ($dbs,$q,$arr = array(),$stmt = null)
{
    global $mysqli;
    if ($mysqli)
        return QQZ_MySQL($mysqli,$q,$arr,$stmt);
    else
        return QQZ_SQLite($dbs,$q,$arr,$stmt);
}

function QQ($q,$arr = array(),$stmt = null)
{
	global $db;
    if (!is_array($arr)) die("QQ passed not an array.");
    return QQZ($db,$q,$arr,$stmt);
}




function PrepareDatabase()
{
    QQ("CREATE TABLE IF NOT EXISTS USERS (ID INTEGER PRIMARY KEY,USERNAME TEXT,LASTNAME TEXT,FIRSTNAME TEXT,TITLE TEXT,EMAIL TEXT,CLASSIFIED INTEGER)");
    QQ("CREATE TABLE IF NOT EXISTS ORGANIZATIONS (ID INTEGER PRIMARY KEY,NAME TEXT,NAMEEN TEXT,LIMITCODES TEXT,SHDECODE TEXT,SHDECLIENT TEXT,SHDESECRET TEXT,SHDECLIENT2 TEXT,SHDESECRET2 TEXT,SHDEPRODUCTION INTEGER)");
    QQ("CREATE TABLE IF NOT EXISTS ENDPOINTS (ID INTEGER PRIMARY KEY,OID INTEGER,PARENT INTEGER,NAME TEXT,NAMEEN TEXT,EMAIL TEXT,ALIASEMAIL TEXT,LIMITCODES TEXT,T0 TEXT,T1 TEXT,T2 TEXT,T3 TEXT,T4 TEXT,T5 TEXT,T6 TEXT,T7 TEXT,T8 TEXT,T9 TEXT,A1 TEXT,A2 TEXT,A3 TEXT,TEL1 TEXT,TEL2 TEXT,TEL3 TEXT,SHDECODE TEXT,INTERNALCODE TEXT,INACTIVE INTEGER,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS FOLDERS (ID INTEGER PRIMARY KEY,EID INTEGER,SPECIALID INTEGER,NAME TEXT,PARENT INTEGER,CLASSIFIED INTEGER,FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ROLES (ID INTEGER PRIMARY KEY,UID INTEGER,ROLEID INTEGER,OID INTEGER,EID INTEGER,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS DOCUMENTS (ID INTEGER PRIMARY KEY,UID INTEGER,EID INTEGER,TOPIC TEXT,FID INTEGER,CLASSIFIED INTEGER,PRIORITY INTEGER,READSTATE INTEGER,PROT TEXT,TYPE INTEGER,CLSID TEXT,RECPX TEXT,RECPY TEXT,FROMX TEXT,FROMY TEXT,FROMZ TEXT,SHDERECEIPT TEXT,SHDEPROTOCOL TEXT,SHDEPROTOCOLDATE INTEGER,SHDEVERSION TEXT,SHDECHECKSENT TEXT,RECPZ TEXT,KOINX TEXT,KOINY TEXT,KOINZ TEXT,BCCX TEXT,BCCY TEXT,BCCZ TEXT,ESWX TEXT,COLOR TEXT,DUEDATE INTEGER,RELATED TEXT,DIAVGEIAID TEXT,KIMDISID TEXT,CATEGORY INTEGER,ADDEDSIGNERS TEXT,METADATA TEXT,ENTRYCREATED INTEGER,PDFPASSWORD TEXT,FOREIGN KEY (FID) REFERENCES FOLDERS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS MESSAGES (ID INTEGER PRIMARY KEY,UID INTEGER,DID INTEGER,MSG TEXT,DATE INTEGER,INFO TEXT,MIME TEXT,SIGNEDPDF BLOB,FOREIGN KEY (DID) REFERENCES DOCUMENTS(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS COMMENTS (ID INTEGER PRIMARY KEY,UID INTEGER,DID INTEGER,MID INTEGER,COMMENT TEXT,DATE INTEGER,FOREIGN KEY (DID) REFERENCES DOCUMENTS(ID),FOREIGN KEY (MID) REFERENCES MESSAGES(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ATTACHMENTS (ID INTEGER PRIMARY KEY,MID INTEGER,NAME TEXT,TYPE TEXT,DESCRIPTION TEXT,DATA BLOB,SHDEID TEXT,FOREIGN KEY (MID) REFERENCES MESSAGES(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ADDRESSBOOK (ID INTEGER PRIMARY KEY,OID INTEGER,EID INTEGER,SHDE INTEGER,CLASSIFIED INTEGER,PARENT INTEGER,LASTNAME TEXT,FIRSTNAME TEXT,TITLE TEXT,EMAIL TEXT,A1 TEXT,A2 TEXT,A3 TEXT,TELS TEXT,DATA TEXT,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ORGCHART (ID INTEGER PRIMARY KEY,CODE TEXT,ROOTCODE TEXT,NAME TEXT,ACTIVE INTEGER,SDDD INTEGER,PARENT INTEGER,FULLNAME TEXT,CODE2 TEXT)");
    QQ("CREATE TABLE IF NOT EXISTS PUSH (ID INTEGER PRIMARY KEY,UID INTEGER,PUSH TEXT,FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS BIO_INFO (ID INTEGER PRIMARY KEY,UID INTEGER,T1 TEXT,T2 TEXT,FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS APIKEYS (ID INTEGER PRIMARY KEY,UID INTEGER,T1 TEXT,FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS SHDESENT (ID INTEGER PRIMARY KEY,RECEIPTID TEXT,RECEIPTDATE INTEGER,PROT TEXT,VERSION TEXT,TYPE TEXT,ORIGINATORID TEXT,RECEIPIENTID TEXT,LOCALRECEPIENTID TEXT,LINKS TEXT)");
    QQ("CREATE TABLE IF NOT EXISTS PENDINGMAIL (ID INTEGER PRIMARY KEY,MESSAGE TEXT)");
    QQ("CREATE TABLE IF NOT EXISTS RULES (ID INTEGER PRIMARY KEY,OID INTEGER,EID INTEGER,TITLE TEXT,CONDITIONS TEXT,ACTIONS TEXT,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS LOGS (ID INTEGER PRIMARY KEY,OID INTEGER,EID INTEGER,DATE INTEGER,DESCRIPTION TEXT,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
}

function PrepareDatabaseMySQL()
{
    QQ("CREATE TABLE IF NOT EXISTS USERS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,USERNAME TEXT,LASTNAME TEXT,FIRSTNAME TEXT,TITLE TEXT,EMAIL TEXT,CLASSIFIED INTEGER)");
    QQ("CREATE TABLE IF NOT EXISTS ORGANIZATIONS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,NAME TEXT,NAMEEN TEXT,LIMITCODES TEXT,SHDECODE TEXT,SHDECLIENT TEXT,SHDESECRET TEXT,SHDECLIENT2 TEXT,SHDESECRET2 TEXT,SHDEPRODUCTION INTEGER)");
    QQ("CREATE TABLE IF NOT EXISTS ENDPOINTS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,OID INTEGER,PARENT INTEGER,NAME TEXT,NAMEEN TEXT,EMAIL TEXT,ALIASEMAIL TEXT,LIMITCODES TEXT,T0 TEXT,T1 TEXT,T2 TEXT,T3 TEXT,T4 TEXT,T5 TEXT,T6 TEXT,T7 TEXT,T8 TEXT,T9 TEXT,A1 TEXT,A2 TEXT,A3 TEXT,TEL1 TEXT,TEL2 TEXT,TEL3 TEXT,SHDECODE TEXT,INTERNALCODE TEXT,INACTIVE INTEGER,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS FOLDERS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,EID INTEGER,SPECIALID INTEGER,NAME TEXT,PARENT INTEGER,CLASSIFIED INTEGER,FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ROLES (ID INTEGER PRIMARY KEY AUTO_INCREMENT,UID INTEGER,ROLEID INTEGER,OID INTEGER,EID INTEGER,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS DOCUMENTS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,UID INTEGER,EID INTEGER,TOPIC TEXT,FID INTEGER,CLASSIFIED INTEGER,PRIORITY INTEGER,READSTATE INTEGER,PROT TEXT,TYPE INTEGER,CLSID TEXT,RECPX TEXT,RECPY TEXT,FROMX TEXT,FROMY TEXT,FROMZ TEXT,SHDERECEIPT TEXT,SHDEPROTOCOL TEXT,SHDEPROTOCOLDATE INTEGER,SHDEVERSION TEXT,SHDECHECKSENT TEXT,RECPZ TEXT,KOINX TEXT,KOINY TEXT,KOINZ TEXT,BCCX TEXT,BCCY TEXT,BCCZ TEXT,ESWX TEXT,COLOR TEXT,DUEDATE INTEGER,RELATED TEXT,DIAVGEIAID TEXT,KIMDISID TEXT,CATEGORY INTEGER,ADDEDSIGNERS TEXT,METADATA TEXT,ENTRYCREATED INTEGER,PDFPASSWORD TEXT,FOREIGN KEY (FID) REFERENCES FOLDERS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS MESSAGES (ID INTEGER PRIMARY KEY AUTO_INCREMENT,UID INTEGER,DID INTEGER,MSG TEXT,DATE INTEGER,INFO TEXT,MIME TEXT,SIGNEDPDF BLOB,FOREIGN KEY (DID) REFERENCES DOCUMENTS(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS COMMENTS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,UID INTEGER,DID INTEGER,MID INTEGER,COMMENT TEXT,DATE INTEGER,FOREIGN KEY (DID) REFERENCES DOCUMENTS(ID),FOREIGN KEY (MID) REFERENCES MESSAGES(ID),FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ATTACHMENTS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,MID INTEGER,NAME TEXT,TYPE TEXT,DESCRIPTION TEXT,DATA BLOB,SHDEID TEXT,FOREIGN KEY (MID) REFERENCES MESSAGES(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ADDRESSBOOK (ID INTEGER PRIMARY KEY AUTO_INCREMENT,OID INTEGER,EID INTEGER,SHDE INTEGER,CLASSIFIED INTEGER,PARENT INTEGER,LASTNAME TEXT,FIRSTNAME TEXT,TITLE TEXT,EMAIL TEXT,A1 TEXT,A2 TEXT,A3 TEXT,TELS TEXT,DATA TEXT,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS ORGCHART (ID INTEGER PRIMARY KEY AUTO_INCREMENT,CODE TEXT,ROOTCODE TEXT,NAME TEXT,ACTIVE INTEGER,SDDD INTEGER,PARENT INTEGER,FULLNAME TEXT,CODE2 TEXT)");
    QQ("CREATE TABLE IF NOT EXISTS PUSH (ID INTEGER PRIMARY KEY AUTO_INCREMENT,UID INTEGER,PUSH TEXT,FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS BIO_INFO (ID INTEGER PRIMARY KEY AUTO_INCREMENT,UID INTEGER,T1 TEXT,T2 TEXT,FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS APIKEYS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,UID INTEGER,T1 TEXT,FOREIGN KEY (UID) REFERENCES USERS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS SHDESENT (ID INTEGER PRIMARY KEY AUTO_INCREMENT,RECEIPTID TEXT,RECEIPTDATE INTEGER,PROT TEXT,VERSION TEXT,TYPE TEXT,ORIGINATORID TEXT,RECEIPIENTID TEXT,LOCALRECEPIENTID TEXT,LINKS TEXT)");
    QQ("CREATE TABLE IF NOT EXISTS PENDINGMAIL (ID INTEGER PRIMARY KEY AUTO_INCREMENT,MESSAGE TEXT)");
    QQ("CREATE TABLE IF NOT EXISTS RULES (ID INTEGER PRIMARY KEY AUTO_INCREMENT,OID INTEGER,EID INTEGER,TITLE TEXT,CONDITIONS TEXT,ACTIONS TEXT,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
    QQ("CREATE TABLE IF NOT EXISTS LOGS (ID INTEGER PRIMARY KEY AUTO_INCREMENT,OID INTEGER,EID INTEGER,DATE INTEGER,DESCRIPTION TEXT,FOREIGN KEY (OID) REFERENCES ORGANIZATIONS(ID),FOREIGN KEY (EID) REFERENCES ENDPOINTS(ID))");
}



function CreateSpecialFoldersForEndpoint($eid)
{
    if (!QQ("SELECT * FROM FOLDERS WHERE EID = ?",array($eid))->fetchArray())
    {
        QQ("INSERT INTO FOLDERS (EID,SPECIALID,NAME) VALUES (?,?,?)",array($eid,FOLDER_INBOX,"Εισερχόμενα"));
        QQ("INSERT INTO FOLDERS (EID,SPECIALID,NAME) VALUES (?,?,?)",array($eid,FOLDER_OUTBOX,"Εξερχόμενα"));
        QQ("INSERT INTO FOLDERS (EID,SPECIALID,NAME) VALUES (?,?,?)",array($eid,FOLDER_SENT,"Απεσταλμένα"));
        QQ("INSERT INTO FOLDERS (EID,SPECIALID,NAME) VALUES (?,?,?)",array($eid,FOLDER_TRASH,"Trash"));
        QQ("INSERT INTO FOLDERS (EID,SPECIALID,NAME) VALUES (?,?,?)",array($eid,FOLDER_ARCHIVE,"Αρχείο"));
    }
}


function GetBinary($table,$column,$id)
{
    global $db;
    $stream = $db->openBlob($table, $column, $id);
    $s = stream_get_contents($stream);
    fclose($stream); // mandatory, otherwise the next line would fail
    return $s;
}

// Sqlite
if (strstr($dbxx,':'))
{
    // MySQL
    $mysqli = new mysqli($dbxx,"root","root","db1");
    PrepareDatabaseMySQL();
}
else
{
    if (!file_exists($dbxx)) 
        $mustprepare = 1;
    $db = new SQLite3($dbxx);
    $db->busyTimeout(10000);
    $db->exec('PRAGMA journal_mode = wal;');
    if ($mustprepare)
        PrepareDatabase();
}