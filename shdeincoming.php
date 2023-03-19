<?php

require_once "functions.php";
$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

// Also temp mails
require_once "vendor/autoload.php";
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;    
require_once "printit.php";
require_once "pdfstuff.php";

function SendAsMail($did,$mid)
{
    xdebug_break();
    global $title;
    $err = 0;
    $q = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($did))->fetchArray();
    if (!$q)
        return $err;

    $dr = DRow($did);
    $msg = MRow($mid);
    $epr = EPRow($dr['EID']);
    if ($epr['FORWARDEMAIL'] && strlen($epr['FORWARDEMAIL']) > 1)
    {
        try
        {
            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";
            $fromm = $epr['ALIASEMAIL'];
            if ($fromm == '')
                $fromm = $topmail;
            $mail->setFrom($fromm,$title);
            $mail->addReplyTo($epr['EMAIL'],$epr['NAME']);
            $mail->isMail();
            
            $mail->Subject = $dr['TOPIC'];
            $mail->isHTML(true);

            $mail->DKIM_domain = MAIL_DOMAIN;
            $mail->DKIM_private = MAIL_RSA_PRIV;
            $mail->DKIM_selector = MAIL_SELECTOR;
            $mail->DKIM_passphrase = MAIL_RSA_PASSPHRASE;
            $mail->DKIM_identity = MAIL_IDENTITY;
                        
            $message = sprintf('Προώθηση από ΣΗΔΕ με θέμα: <br><b>%s</b><br>',$dr['TOPIC']);
            $pdff = GetBinary("MESSAGES","SIGNEDPDF",$msg['ID']);
            if (strlen($pdff))
                $mail->addStringAttachment($pdff,"document.pdf");
    
            // Attachments
            $q5 = QQ("SELECT * FROM ATTACHMENTS WHERE MID = ?",array($msg['ID']));
            while($r5 = $q5->fetchArray())
            {
                $f1 = tempnam(sys_get_temp_dir(),"tmp");
                $todel[] = $f1;
                $s = GetBinary('ATTACHMENTS','DATA',$r5['ID']);
                if ($dr['CLASSIFIED'] > 0)
                    $s = ed($s,$pwd,'d');

                file_put_contents($f1,$s);
                if ($dr['CLASSIFIED'] > 0)
                {
                    $mail->addAttachment($f1,ed($r5['NAME'],$pwd,'d'));
                    $message .= sprintf('<br>Συνημμένο: <b>%s</b> [%s] <br>',ed($r5['NAME'],$pwd,'d'),ed($r5['DESC'],$pwd,'d'));

                }
                else
                {
                    $mail->addAttachment($f1,$r5['NAME']);
                    $message .= sprintf('<br>Συνημμένο: <b>%s</b> [%s] <br>',$r5['NAME'],$r5['DESC']);
                }
            }
            $hasR = 0;
            $mail->addAddress($epr['FORWARDEMAIL'],$epr['FORWARDEMAIL']);

            $mail->Body = $message;
            $r =  $mail->send();
            if ($r)
                {
                    $err = 0;
                }
            else
                $err = 1;
        }
        catch(phpmailerException $e)
        {
            $err = 1;
        }

    }
    return $err;
}

function RunRules($did)
{
    $q = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($did))->fetchArray();
    if (!$q)
        return;

    $eid = $q['EID'];
    $er = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($eid))->fetchArray();
    $fr = QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($er['OID']))->fetchArray();
    $qr = QQ("SELECT * FROM RULES");
    while($rr = $qr->fetchArray())
    {
        $c = json_decode($rr['CONDITIONS']);
        if (!$c)
            continue;
        $actions = json_decode($rr['ACTIONS']);
        if (!$actions)
            continue;

        $nyes = 0;
        $nno = 0;
        $nall = 0;

        // frommail
        if (property_exists($c,"frommail"))
        {
            $fromz = $q['FROMZ'];
            $pattern = '/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i';
            preg_match_all($pattern, $fromz, $matches);
            $matchmail = $matches[0][0];

            $matches = null;
            preg_match_all($c->frommail, $matchmail, $matches);
            $nall++;
            if (count($matches))
            {
                $matchmail = $matches[0];

                if ($matchmail == null)
                {
                    $nno++;
                }
                else
                {
                    $nyes++;
                }
            }
        }


        if ($nno > 0 && $rr['ANDOR'] == 1)
            continue; 
        if ($nyes == 0)
            continue;

        // Matching, do action
        if (property_exists($actions,"movefolder"))
        {
            $fidr = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($actions->movefolder))->fetchArray();
            if ($fidr)
                QQ("UPDATE DOCUMENTS SET FID = ? WHERE ID = ?",array($actions->movefolder,$q['ID']));
        }

    }

}



$totalin = 0;

if (file_exists($dbxxpending))
{
    $db2 = new SQLite3($dbxxpending);
    $q = QQZ_SQLite($db2,"SELECT * FROM PENDINGMAIL");
    while($r = $q->fetchArray())
    {
        // use an instance of MailMimeParser as a class dependency
        $mailParser = new MailMimeParser();
        $message = Message::from($r['MESSAGE'], false);

        $fromm = $message->getHeaderValue(HeaderConsts::FROM);
        $fromn = $message->getHeader(HeaderConsts::FROM)->getPersonName(); 
        $subject = $message->getHeaderValue(HeaderConsts::SUBJECT);
        $tos = $message->getHeader(HeaderConsts::TO)->getAddresses();
        $recpz = '';
        $plain = $message->getTextContent();
        $html = $message->getHtmlContent();

        if ($html == null)
            $html = $plain;
        if ($html == null)
            continue;
        
        $fromz = sprintf("%s (%s)",$fromn,$fromm);
        $prot = NewProtocol($subject,0,$fromz,0);       
        foreach($tos as $to)   
        {
            $ton = $to->getName();
            $tom = $to->getEmail();

            $qeid = QQ("SELECT * FROM ENDPOINTS WHERE ALIASEMAIL = ?",array($tom))->fetchArray();
            if (!$qeid)
                continue;

            $fid = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND SPECIALID = ?",array($qeid['ID'],FOLDER_INBOX))->fetchArray()[0];
            $recpx = array();

            QQ("INSERT INTO DOCUMENTS (EID,READSTATE,TOPIC,FID,PROT,CLSID,RECPX,FROMZ,TYPE) VALUES (?,?,?,?,?,?,?,?,?)",array(
                    $qeid['ID'],1,$subject,$fid,serialize($prot),guidv4(),serialize($recpx),$fromz,1
                ));
            $did = $lastRowID;
            QQ("INSERT INTO MESSAGES (DID,MSG,DATE) VALUES (?,?,?)",array(
                    $did,$html,time()
                ));
            $mid = $lastRowID;

            $ac = $message->getAttachmentCount();
            for($i = 0 ; $i < $ac ; $i++)
            {
                $at = $message->getAttachmentPart($i);
                $fname = $at->getFilename();
                $stream = $at->getContent();
                $ty = $at->getContentType();
                QQ("INSERT INTO ATTACHMENTS (MID,NAME,TYPE,DESCRIPTION,DATA) VALUES(?,?,?,?,?)",array(
                    $mid,$fname,$ty,"",$stream
                ));
            }
            RunRules($did);   
            $totalin++; 
        }
    }
    $db2->close();
    $db2 = null;
    unlink($dbxxpending);
}


$archive = 0;
if (array_key_exists("archive",$req))
    $archive = $req['archive'];
$oid = 0;
if (array_key_exists("oid",$req))
    $oid = $req['oid'];


function PostReceipt($oid,$docprot,$docversion,$clsid,$protx)
{
    global $siteroot;
    global $archive;
    if ($archive == 1 || $archive == 2)
        return true;
    $c = curl_init();
    $statusurl = ShdeUrl($oid).'/receipts';
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_URL, $statusurl );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    curl_setopt($c, CURLOPT_CUSTOMREQUEST, "POST");
    $authorization = GetBearer($oid);
    if ($authorization == '')
        return false;
    $finalreceipt = sprintf("%s %s",$protx['n'],date("Y-m-d H:i:s",$protx['t']));
    $params2 = array("ReceiptDate" => date("Y-m-d H:i:s"),"DocumentProtocolNo" => $docprot,"VersionNumber" => $docversion,"LocalReceiptId" => $finalreceipt);
    curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($params2));
    curl_setopt($c, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        $authorization  
        ));
    $rs = curl_exec($c);
    $j = json_decode($rs);
    return true;
}

function GetAttachments($atts,$docprot,$oid,$did,$mid)
{
    global $lastRowID;
    global $siteroot;
    $vu = true;

    foreach($atts->results as $att)
    {
        $e = QQ("SELECT * FROM ATTACHMENTS WHERE SHDEID = ?",array($att->AttachmentId))->fetchArray();
        if (!$e)
            {
            $c = curl_init();
            $statusurl = ShdeUrl($oid).'/documents/'.$docprot.'/attachments/'.$att->AttachmentId.'/content';
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($c, CURLOPT_AUTOREFERER,    1);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($c, CURLOPT_URL, $statusurl );
            curl_setopt($c, CURLOPT_REFERER, $siteroot);
            $authorization = GetBearer($oid);
            if ($authorization == '')
                {
                    $vu = false;
                    continue;
                }
            curl_setopt($c, CURLOPT_HTTPHEADER, array(
                    $authorization
                ));

            $content = curl_exec($c);
            $mime = curl_getinfo($c,CURLINFO_CONTENT_TYPE);
            if ($mime == "application/octet-stream")
                $mime = mime_type($att->FileName);
            if (strlen($content) == 0)
                {
                    $vu = false;
                    continue;
                }
            $lastRowID = 0;
            QQ("INSERT INTO ATTACHMENTS (MID,NAME,TYPE,DESC,SHDEID,DATA) VALUES(?,?,?,?,?,?)",array(
                $mid,$att->FileName,$mime,"Attachment",$att->AttachmentId,$content
            ));
            if ($lastRowID == 0)
                $vu = false;
        }
        else
        {

            QQ("INSERT INTO ATTACHMENTS (MID,NAME,TYPE,DESC,SHDEID,DATA) VALUES(?,?,?,?,?,?)",array(
                $mid,$e['NAME'],$e['TYPE'],"Attachment",$att->AttachmentId,GetBinary('ATTACHMENTS','DATA',$e['ID'])
            ));
        }
    }

    return $vu;
}


$endpage = 0;
for($page = 1; ; $page++)
{
    if ($endpage)
        break;
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_REFERER, $siteroot);

    $q1 = QQ("SELECT * FROM ORGANIZATIONS");

    $sfid = FOLDER_INBOX;
    if ($archive == 1 || $archive == 2)
        $sfid = FOLDER_ARCHIVE;

    while($r1 = $q1->fetchArray())
    {
        if ($r1['ID'] != $oid && $oid != 0)
            continue;
        $base = sprintf("%s%s?page=%s",ShdeUrl($r1['ID']),'/documents/',$page);
        if ($archive == 1)
            $base = sprintf("%s%s?page=%s&status=1&dateFrom=%s",ShdeUrl($r1['ID']),'/documents/',$page,urlencode(date("Y-m-d H:i:s",time() - 86400*30)));
        if ($archive == 2)
            $base = sprintf("%s%s?page=%s&status=2&dateFrom=%s",ShdeUrl($r1['ID']),'/documents/',$page,urlencode(date("Y-m-d H:i:s",time() - 86400*30)));
        curl_setopt($c, CURLOPT_URL, $base );
        $ac = UserAccessOID($r1['ID'],$u->uid);
        if ($ac == 0)
            continue;

        $oid_index = $r1['ID'];

        $authorization = GetBearer($r1['ID']);
        if ($authorization == '')
            continue;

        curl_setopt($c, CURLOPT_HTTPHEADER, array(
                "Content-Type: multipart/form-data",
                $authorization
            ));
            
        $r = curl_exec($c);
        $j = json_decode($r);    


        if (!property_exists($j,"results"))
        {
            redirect("index.php?resetshde=1");
            die;
        }
        if (count($j->results) == 0)
            {
                $endpage = 1;
                break;
            }

    /*
    DIAVGEIAID	text NULL
    KIMDISID	text NULL
    */    
        foreach($j->results as $d)
        {
            // Check existing
            $ex1 = QQ("SELECT * FROM DOCUMENTS WHERE SHDEPROTOCOL = ? AND SHDEVERSION = ? AND SHDERECEIPT IS NULL",array($d->ProtocolNo,$d->VersionNumber))->fetchArray();
            if ($ex1)
                continue;
            $ex2 = QQ("SELECT * FROM DOCUMENTS WHERE SHDEPROTOCOL = ? AND SHDERECEIPT IS NULL",array($d->ProtocolNo))->fetchArray();
            if ($ex2)
            {
                DeleteDocument($ex2['ID'],2);
            }

            $clsf = 0;
            if ($d->Classification == 1)
                $clsf = 1;

            $related = "";
            if (property_exists($d,"RelatedDocumentProtocolNo"))
            {
                $relpro = $d->RelatedDocumentProtocolNo;
                $reltype = $d->RelatedDocumentType;
                $qrel = QQ("SELECT * FROM DOCUMENTS WHERE SHDEPROTOCOL = ?",array($relpro))->fetchArray();
                if ($qrel)
                {
                    if ($reltype == 2)
                        $related = sprintf("%d",-$qrel['ID']);
                    else
                        $related = sprintf("%d",$qrel['ID']);
                }
            }
            // Get Status
            if (0)
            {
                $c = curl_init();
                $statusurl = ShdeUrl($oid_index).'/documents/'.$d->ProtocolNo.'/status';
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($c, CURLOPT_AUTOREFERER,    1);
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($c, CURLOPT_URL, $statusurl);
                curl_setopt($c, CURLOPT_REFERER, $siteroot);
                curl_setopt($c, CURLOPT_HTTPHEADER, array(
                    $authorization
                ));
                $statc = curl_exec($c);
            }

            // Get Content
            $typx = 0;
            if (1)
            {
                $c = curl_init();
                $statusurl = ShdeUrl($oid_index).'/documents/'.$d->ProtocolNo.'/content';
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($c, CURLOPT_AUTOREFERER,    1);
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($c, CURLOPT_URL, $statusurl);
                curl_setopt($c, CURLOPT_REFERER, $siteroot);
                curl_setopt($c, CURLOPT_HTTPHEADER, array(
                    $authorization
                ));
                $content = curl_exec($c);
                $mime = curl_getinfo($c,CURLINFO_CONTENT_TYPE);
                if ($mime == "application/octet-stream")
                    $mime = mime_type($d->FileName);
                if ($mime == "text/html")
                    $typx = 1;
                if ($mime != "application/pdf")
                    $typx = 2;
            }

            // Get Attachments
            if (1)
            {
                $c = curl_init();
                $statusurl = ShdeUrl($oid_index).'/documents/'.$d->ProtocolNo.'/attachments';
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($c, CURLOPT_AUTOREFERER,    1);
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($c, CURLOPT_URL, $statusurl);
                curl_setopt($c, CURLOPT_REFERER, $siteroot);
                curl_setopt($c, CURLOPT_HTTPHEADER, array(
                    $authorization
                ));
                $attachments = json_decode(curl_exec($c));
            }

            $fromcode = $d->SenderSectorCode;
            $fromdep = (int)$d->SenderSectorDepartment;
            if ($fromdep == 0 && strlen($d->SenderSectorDepartment))
            {
               $mu1 = QQ("SELECT * FROM ORGCHART WHERE NAME = ? AND ROOTCODE = ?",array($d->SenderSectorDepartment,$fromcode))->fetchArray();
               if ($mu1)
                    $fromdep = $mu1['CODE2'];
            }
            $fullcode = $fromcode;
            if ($fromdep)
                $fullcode = sprintf("%s|%s",$fromcode,$fromdep);

            $fromsendername = '';
            $mu1 = QQ("SELECT * FROM ORGCHART WHERE CODE = ?",array($fullcode))->fetchArray();
            if ($mu1)
                $fromsendername = $mu1['NAME'];

            $recpx = array();        
            foreach($d->RecipientSectorCodes as $k1)
            {
                $recpx[] = $k1;
            }
            if (count($d->RecipientSectorDepartments))
                $recpx = array();
            foreach($d->RecipientSectorDepartments as $k2)
            {
                $recpx[] = sprintf("%s|%s",$k2->SectorCode,$k2->DepartmentCode);
            }
            $recpx = array_unique($recpx);


            $koinx = array();
            foreach($d->CCSectorCodes as $k1)
            {
                $koinx[] = $k1;
            }
            if (count($d->CCSectorDepartments))
                $koinx = array();
            foreach($d->CCSectorDepartments as $k2)
            {
                $koinx[] = sprintf("%s|%s",$k2->SectorCode,$k2->DepartmentCode);
            }
            $koinx = array_unique($koinx);

            foreach($d->RecipientSectorCodes as $recpcode)
            {
                $oidr = QQ("SELECT * FROM ORGANIZATIONS WHERE SHDECODE = ?",array($recpcode))->fetchArray();
                if (!$oidr)
                    continue;

                if (count($d->RecipientSectorDepartments) == 0)
                    $d->RecipientSectorDepartments[] = json_decode(json_encode(array(
                        "SectorCode" => $recpcode,
                        "DepartmentCode" => QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($oidr['ID']))->fetchArray()['ID'],
                    )));
                foreach($d->RecipientSectorDepartments as $endpointsub)
                {
                    $eidr = QQ("SELECT * FROM ENDPOINTS WHERE OID = ? AND ID = ?",array($oidr['ID'],$endpointsub->DepartmentCode))->fetchArray();
                    if (!$eidr)
                        $eidr = QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($oidr['ID']))->fetchArray();
                    if (!$eidr)
                        continue;

                    $ex = QQ("SELECT * FROM DOCUMENTS WHERE SHDEPROTOCOL = ? AND SHDERECEIPT IS NULL AND EID = ?",array($d->ProtocolNo,$eidr['ID']))->fetchArray();
                    if ($ex)
                        continue;
                    $due = 0;
                    if ($d->DueDate)
                        $due = strtotime($d->DueDate);
                    $clsid = guidv4();
                    $fid = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND SPECIALID = ?",array($eidr['ID'],$sfid))->fetchArray();

                    // Prot
                    if ($archive == 0)
                        $prot = NewProtocol($d->Subject,0,$fromsendername,0);       
                    else
                        $prot = array("n" => "1", "id" => "1", "t" => time(),"l" => 0);

                    QQ("INSERT INTO DOCUMENTS (PROT,RELATED,TYPE,CLASSIFIED,EID,TOPIC,SHDEPROTOCOL,SHDEPROTOCOLDATE,SHDEVERSION,FROMX,FID,METADATA,CATEGORY,READSTATE,CLSID,DUEDATE,KOINX,RECPX) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",array(
                        serialize($prot),$related,$typx,$clsf,$eidr['ID'],$d->Subject,$d->ProtocolNo,strtotime($d->ProtocolDate),$d->VersionNumber,serialize(array($fullcode)),$fid['ID'],$r,$d->Category,1,$clsid,$due,serialize($koinx),serialize($recpx),
                    ));
                    $did = $lastRowID;
                    QQ("INSERT INTO MESSAGES (DID,MSG,DATE,MIME,SIGNEDPDF) VALUES (?,?,?,?,?)",array(
                        $did,'',time(),$mime,$content
                    ));
                    $mid = $lastRowID;
                    if ($mid && $did)
                    {
                        if (GetAttachments($attachments,$d->ProtocolNo,$oid_index,$did,$mid))   
                            {
                                PostReceipt($oid_index,$d->ProtocolNo,$d->VersionNumber,$clsid,$prot);
                                SendAsMail($did,$mid);
                            }
                    }
                    $totalin++;
                    RunRules($did);    
                }
            }

            foreach($d->CCSectorCodes as $recpcode)
            {
                $oidr = QQ("SELECT * FROM ORGANIZATIONS WHERE SHDECODE = ?",array($recpcode))->fetchArray();
                if (!$oidr)
                    continue;

                if (count($d->CCSectorDepartments) == 0)
                    $d->CCSectorDepartments[] = json_decode(json_encode(array(
                        "SectorCode" => $recpcode,
                        "DepartmentCode" => QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($oidr['ID']))->fetchArray()['ID'],
                    )));
                foreach($d->CCSectorDepartments as $endpointsub)
                {
                    $eidr = QQ("SELECT * FROM ENDPOINTS WHERE OID = ? AND ID = ?",array($oidr['ID'],$endpointsub->DepartmentCode))->fetchArray();
                    if (!$eidr)
                        $eidr = QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($oidr['ID']))->fetchArray();
                    if (!$eidr)
                        continue;

                    $ex = QQ("SELECT * FROM DOCUMENTS WHERE SHDEPROTOCOL = ? AND SHDERECEIPT IS NULL AND EID = ?",array($d->ProtocolNo,$eidr['ID']))->fetchArray();
                    if ($ex)
                        continue;
                    $due = 0;
                    if ($d->DueDate)
                        $due = strtotime($d->DueDate);
                    $clsid = guidv4();
                    $fid = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND SPECIALID = ?",array($eidr['ID'],$sfid))->fetchArray();
                    if ($archive == 0)
                        $prot = NewProtocol($d->Subject,0,$fromsendername,0);       
                    else
                        $prot = array("n" => "1", "id" => "1", "t" => time(),"l" => 0);

                    QQ("INSERT INTO DOCUMENTS (PROT,RELATED,TYPE,CLASSIFIED,EID,TOPIC,SHDEPROTOCOL,SHDEPROTOCOLDATE,SHDEVERSION,FROMX,FID,METADATA,CATEGORY,READSTATE,CLSID,DUEDATE,KOINX,RECPX) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",array(
                        serialize($prot),$related,$typx,$clsf,$eidr['ID'],$d->Subject,$d->ProtocolNo,strtotime($d->ProtocolDate),$d->VersionNumber,serialize(array($fullcode)),$fid['ID'],$r,$d->Category,1,$clsid,$due,serialize($koinx),serialize($recpx),
                    ));
                    $did = $lastRowID;
                    QQ("INSERT INTO MESSAGES (DID,MSG,DATE,MIME,SIGNEDPDF) VALUES (?,?,?,?,?)",array(
                        $did,'',time(),$mime,$content
                    ));
                    $mid = $lastRowID;
                    if ($mid && $did)
                    {
                        if (GetAttachments($attachments,$d->ProtocolNo,$oid_index,$did,$mid))
                            {
                                PostReceipt($oid_index,$d->ProtocolNo,$d->VersionNumber,$clsid,$prot);
                                SendAsMail($did,$mid);
                            }
                    }
                    $totalin++;
                    RunRules($did);    
                }
            }

        }
    }
}
if ($totalin > 0)
    $_SESSION['notif'] = sprintf("Νέα εισερχόμενα: %d",$totalin);
redirect($whereret);

/*

FileName (string, optional),
Content (string, optional),
Subject (string, optional),
Comments (string, optional)
Category (string, optional): Κατηγορία - 1:
Απόφαση 2: Σύμβαση 3: Λογαριασμός 4:
Ανακοίνωση 5: Άλλο
AuthorName (string, optional),
MetadataJson (string, optional),
DueDate (string, optional),
DiavgiaId (string, optional): ΑΔΑ ,
KimdisId (string, optional): ΚΗΜΔΗΣ ,
DocumentId (integer, optional),
ProtocolNo (string, optional),
ProtocolDate (string, optional),
DocumentStatus (SideDocumentState,
optional): Κατάσταση Εγγράφου,
RelatedDocumentProtocolNo (string,
optional): Σχετικό Έγγραφο,
Version (SideDocumentVersion, optional):
Versioning Properties,
SenderSectorId (integer, optional),
SenderSectorCode (string, optional),
SenderSectorDirectorate (string, optional),
SenderSectorDepartment (string,
optional),
RecipientSectorCodes (Array[string],
optional),
RecipientSectorDepartments
(Array[SectorDepartmentResource],
optional): Παραλήπτες - Λίστα κωδικών
τμημάτων από οργανόγραμμα φορέα
CCSectorCodes (Array[string], optional),
CCSectorDepartments 

// Convert this document into DOCUMENTS -> MESSAGES
*/
