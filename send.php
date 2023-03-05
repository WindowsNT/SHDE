<?php

require_once "functions.php";
if (!$u)
    diez();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;    
require_once "printit.php";
require_once "pdfstuff.php";
require_once "vendor/autoload.php";

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

//require_once "phpmailerstuff.php";


function ShdeSend($docr,$msgr,$pdf,$pdffile)
{
    global $siteroot;
//    $base = "sylviamichael.hopto.org:7010" . '/documents';

    
    $er = EPRow($docr['EID']);
    $fr = FRow($er['OID']);

    $m = array(
        "OrgChartVersion" => 1,
    );

    $m["FileName"] = sprintf("%s.pdf",$docr['ID']);
    if ($docr['TYPE'] == 0 && $docr['PDFPASSWORD'] && strlen($docr['PDFPASSWORD']) > 0)
        $m["FileName"] = sprintf("%s.zip",$docr['ID']);

    if ($docr['TYPE'] == 1)
         $m["FileName"] = sprintf("%s.html",$docr['ID']);
    $type2c = null;
    $type2m = null;
    if ($docr['TYPE'] == 2)
    {
        $qc = QQ("SELECT * FROM ATTACHMENTS WHERE MID = ?",array($msgr['ID']))->fetchArray();
        if ($qc)
        {
            $m["FileName"] = $qc['NAME'];
            $type2c = GetBinary('ATTACHMENTS','DATA',$qc['ID']);
            $type2m = $qc['TYPE'];
        }
    }

    $m["Subject"] = $docr['TOPIC'];
    if ($docr['CLASSIFIED'] > 0)
        $m["Classification"] = 1;

    $uidr = UserRow($docr['UID']);
    if ($uidr)
        $m["AuthorName"] = sprintf("%s %s",$uidr['LASTNAME'],$uidr['FIRSTNAME']);

    $qc = QQ("SELECT * FROM COMMENTS WHERE DID = ? AND MID = ?",array($docr['ID'],$msgr['ID']));
    $commstr = '';
    while($r9 = $qc->fetchArray())
    {   
        $ur = UserRow($r9['UID']);
        if (!$ur)
            continue;
        $commstr .= sprintf("(%s %s) [%s] %s<br>",$ur['LASTNAME'],$ur['FIRSTNAME'],date("Y-m-d H:i:s",$r9['DATE']),$r9['COMMENT']);
    }
    if (strlen($commstr))
        $m["Comments"] = $commstr;

    $m["Category"] = (int)$docr['CATEGORY'];
    if ($docr['DUEDATE'] && $docr['DUEDATE'] > 0)
        $m["DueDate"] = date("Y-m-d H:i:s",$docr['DUEDATE']);

    if ($docr['RELATED'] && strlen($docr['RELATED']))
    {
        $related = explode(",",$docr['RELATED']);
        foreach($related as $rel)
        {
            $doc2 = DRow(abs($rel));
            if (!$doc2)
                continue;
            if (!$doc2['SHDEPROTOCOL'])
                continue;
            if (strlen($doc2['SHDEPROTOCOL']) == 0)
                continue;

            $m["RelatedDocumentProtocolNo"] = $doc2['SHDEPROTOCOL'];
            if ($rel < 0)
                $m["RelatedDocumentType"] = 2;
            else
                $m["RelatedDocumentType"] = 1;
            break; // one only
        }
    }

    if (1)
    {
        $recp = array();
        $subrecp = array();
        foreach(unserialize($docr['RECPX'])  as $rep)
        {
            $rr = QQ("SELECT * FROM ORGCHART WHERE CODE = ?",array($rep))->fetchArray();
            if (!$rr)
                continue;

            // Code is like x|y
            $codes = explode("|",$rr['CODE']);
            $recp [] = $codes[0];
            if (count($codes) == 2)
                $subrecp[] = array("SectorCode" => $codes[0],"DepartmentCode" => $codes[1]);
        }
        $m["RecipientSectorCodes"] = $recp;
        if (count($subrecp) > 0)
            $m["RecipientSectorDepartments"] = $subrecp;
    }
    if ($docr['KOINX'] && strlen($docr['KOINX']))
    {
        $recp = array();
        $subrecp = array();
        foreach(unserialize($docr['KOINX'])  as $rep)
        {
            $rr = QQ("SELECT * FROM ORGCHART WHERE CODE = ?",array($rep))->fetchArray();
            if (!$rr)
                continue;

            $codes = explode("|",$rr['CODE']);
            $recp [] = $codes[0];
            if (count($codes) == 2)
                $subrecp[] = array("SectorCode" => $codes[0],"DepartmentCode" => $codes[1]);
        }
        $m["CCSectorCodes"] = $recp;
        if (count($subrecp) > 0)
            $m["CCSectorDepartments"] = $subrecp;
    }


    $prot = unserialize($docr['PROT']);
    $m["LocalSectorProtocolNo"] = sprintf("%d",$prot['n']);
    $m["LocalSectorProtocolDate"] = date("Y-m-d H:i:s",$prot['t']);

    $howdeps = QQ("SELECT COUNT(*) FROM ENDPOINTS WHERE OID = ?",array($fr['ID']))->fetchArray()[0];
    if ($howdeps > 1)
        $m["SenderSectorDepartment"] = $er['NAME'];


    $m["IsFinal"] = true;
    $qc = QQ("SELECT * FROM ATTACHMENTS WHERE MID = ?",array($msgr['ID']));
    $atts = array();
    $numat = 0;
    while($r9 = $qc->fetchArray())
    {
        if ($numat == 0 && $docr['TYPE'] == 2)
            {
                $numat++;
                continue;
            }
        $atts [] = $r9;
    }

    if (count($atts))
        $m["IsFinal"] = false;


    $c = curl_init();
    $st = ShdeUrl($fr['ID']).'/documents';

    $loge = sprintf("shde_login_%s",$fr['ID']);
    if (array_key_exists($loge,$_SESSION))
    {
        if (array_key_exists("AccessToken",$_SESSION[$loge]))
        {
            $authorization = "Authorization: Bearer ".$_SESSION[$loge]["AccessToken"]; // Prepare the authorisation token
        }
    }
    if ($authorization == '')
        return false;

    $mustput = 0;
    if ($docr['SHDEPROTOCOL'] && strlen($docr['SHDEPROTOCOL']))
        {
            $mustput = 1;
            $st = ShdeUrl($fr['ID']). '/documents/'.$docr['SHDEPROTOCOL'];
            $m["VersionComments"] = "Ανακοινοποίηση στο Ορθό";
         }

     

    $ue = json_encode($m);
    curl_setopt_array($c, array(
        CURLOPT_URL => $st,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $mustput ? "PUT" : "POST",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "Content-Type: multipart/form-data",
            $authorization
        ),
    ));
    if ($docr['TYPE'] == 2)
    {
        $tx = tempnam("/tmp","prf");
        file_put_contents($tx,$type2c);
        $fields = array(
        'DocumentContent' => new \CurlFile($tx, $type2m, sprintf("%d",$docr['ID']))
        ,'DocumentMetadata' => $ue);
    }
    else
    if ($docr['TYPE'] == 1)
    {
        $tx = tempnam("/tmp","prf");
        file_put_contents($tx,$msgr['MSG']);
        $fields = array(
        'DocumentContent' => new \CurlFile($tx, 'text/html', sprintf("%d.html",$docr['ID']))
        ,'DocumentMetadata' => $ue);
    }
    else
    if ($docr['PDFPASSWORD'] && strlen($docr['PDFPASSWORD']))
        {
        $tx = tempnam("/tmp","zip");

        $zip = new ZipArchive();
        if ($zip->open($tx, ZipArchive::CREATE) === TRUE) 
        {
            $zip->setPassword($docr['PDFPASSWORD']); //set default password
            $zip->addFile($pdffile,sprintf("%d.pdf",$docr['ID']));
            $zip->setEncryptionName(sprintf("%d.pdf",$docr['ID']), ZipArchive::EM_AES_256);
            $zip->close();
            $zip = null;
            $fields = array(
            'DocumentContent' => new \CurlFile($tx, 'application/zip', sprintf("%d.zip",$docr['ID']))
            ,'DocumentMetadata' => $ue);
            }
        }
    else
    {
        $fields = array(
            'DocumentContent' => new \CurlFile($pdffile, 'application/pdf', sprintf("%d.pdf",$docr['ID']))
            ,'DocumentMetadata' => $ue);
    
    }
            
    curl_setopt($c, CURLOPT_POSTFIELDS, $fields);


    $r = curl_exec($c);
    $r2 = json_decode($r,true);

    if (!array_key_exists("ReceiptId",$r2))
        return false;   // failed;

    $countatt = count($atts);
    foreach($atts as $att)
    {
        $tf = tempnam(".","temp");
        unlink($tf);
        $data = GetBinary('ATTACHMENTS','DATA',$att['ID']);
        $datalen = strlen($data);
        file_put_contents($tf,$data);
        $c = curl_init();
        $base2 = ShdeUrl($fr['ID']) . '/documents/'.$r2['DocumentProtocolNo'].'/attachments';
        curl_setopt_array($c, array(
            CURLOPT_URL => $base2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "Content-Type: multipart/form-data",
                $authorization
            ),
        ));

        $countatt--;

        $meta = array("FileName" => $att['NAME'],"IsFinal" => ($countatt == 0) ? true : false);
        $ue = json_encode($meta);

        $fields = array(
            'DocumentContent' => new \CurlFile($tf, $att['TYPE'], $att['NAME']),
           'DocumentMetadata' => $ue);
       curl_setopt($c, CURLOPT_POSTFIELDS, $fields);
       $rAT = curl_exec($c);
       $rAT2 = json_decode($rAT,true);       
       unlink($tf);

       if (!array_key_exists("AttachmentId",$rAT2))
            return false;   // failed;
        QQ("UPDATE ATTACHMENTS SET SHDEID = ? WHERE ID = ?",array($rAT2['AttachmentId'],$att['ID']));
    }

    if ($mustput)
        QQ("UPDATE DOCUMENTS SET SHDERECEIPT = ?,SHDEPROTOCOL = ?,SHDEPROTOCOLDATE = ?,SHDEVERSION = ?,SHDECHECKSENT = ? WHERE ID = ?",array($r2['ReceiptId'],$r2['DocumentProtocolNo'],strtotime($r2['ReceiptDate']),$r2['VersionNumber'],'',$docr['ID']));
    else
        QQ("UPDATE DOCUMENTS SET SHDERECEIPT = ?,SHDEPROTOCOL = ?,SHDEPROTOCOLDATE = ? WHERE ID = ?",array($r2['ReceiptId'],$r2['DocumentProtocolNo'],strtotime($r2['ReceiptDate']),$docr['ID']));
    return true;
}

require_once "output.php";
if (array_key_exists("send",$_POST))
{
    foreach($_POST as $k => $v)
    {
        if (substr($k,0,3) == "ddd" && $v == "on")
        {
            $todel = array();
            $key = substr($k,3);

            $dr = DRow($key,1);
            if (!$dr)
                continue;
            
            $epr = EPRow($dr['EID']);
            if (!$epr)
                continue;
            printf("Sending document ID %s...<br>",$dr['ID']);
            if (UserAccessDocument($dr['ID'],$u->uid) != 2)
                {
                printf("Access denied.<br>",$dr['ID']);
                continue;
                }
            $msg = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($dr['ID']))->fetchArray();
            if (!$msg)
            {
                printf("Not any message.<br>",$dr['ID']);
                continue;
            }
            DocumentDecrypt($msg);
            $err = 1;
            
            $fr = FolderRow($dr['FID']);
            if (!$fr)
            {
                printf("Not in folder.<br>",$dr['ID']);
                continue;
            }

            if (TopFolderType($fr['ID']) != FOLDER_OUTBOX)
            {
                printf("Not in outbox.<br>",$dr['ID']);
                continue;
            }

            $pwd = 0;
            if ($dr['CLASSIFIED'] > 0)
            {
                $pwd = PasswordFromSession($dr['ID']);
                if ($pwd === FALSE)
                {
                    printf("Not decrypted.<br>");
                    continue;
                }
            }


            $pdff = '';
            if ($dr['TYPE'] == 1)
            {

            }
            else
            {
                if (strlen($msg['SIGNEDPDF']) > 5)
                {
                    $pdfmessage = GetBinary("MESSAGES","SIGNEDPDF",$msg['ID']);
                    if ($dr['CLASSIFIED'] > 0)
                        $pdfmessage = ed($pdfmessage,$pwd,'d');
                }
                else
                {
                    printf("Converting to PDF...<br>");
                    $pdfmessage = PDFConvert($epr['NAME'],$dr['TOPIC'],PrintAll($dr['ID'],$msg['ID']),$dr['CLSID']);
                }
                $pdff = tempnam(sys_get_temp_dir(),"pdf");
                $pdff .= ".pdf";
                file_put_contents($pdff,$pdfmessage);
                $todel[] = $pdff;
            }
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

                $message = sprintf('Σας αποστέλλουμε %s μήνυμα από το σύστημα ηλεκτρονικής διακίνησης εγγράφων με θέμα: <br><b>%s</b><br>',ClassificationString($dr['CLASSIFIED'],1), $dr['TOPIC']);

                if ($dr['TYPE'] == 1)   
                    $message = $msg['MSG'];                    
                else
                    $mail->addAttachment($pdff,"document.pdf");

                $mail->DKIM_domain = MAIL_DOMAIN;
                $mail->DKIM_private = MAIL_RSA_PRIV;
                $mail->DKIM_selector = MAIL_SELECTOR;
                $mail->DKIM_passphrase = MAIL_RSA_PASSPHRASE;
                $mail->DKIM_identity = MAIL_IDENTITY;

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

                // Inside shde
                if (strlen($dr['RECPX']) || strlen($dr['KOINX']) )
                {
                    $r = ShdeSend($dr,$msg,$pdfmessage,$pdff);
                    if ($r == true)
                    {
                        $err = 0;
                    }
                    else
                        continue;   
                }
    
                // Outside shde
                if (strlen($dr['RECPY']))
                {
                    foreach(unserialize($dr['RECPY']) as $y)
                    {
                        $y1 = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($y))->fetchArray();
                        if ($y1)
                        {
                            $mail->addAddress($y1['EMAIL'], sprintf("%s %s",$y1['LASTNAME'],$y1['FIRSTNAME']));
                            $hasR++;
                        }
                    }
                }
                if (strlen($dr['KOINY']))
                {
                    foreach(unserialize($dr['KOINY']) as $y)
                    {
                        $y1 = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($y))->fetchArray();
                        if ($y1)
                        {
                            $mail->addCC($y1['EMAIL'], sprintf("%s %s",$y1['LASTNAME'],$y1['FIRSTNAME']));
                            $hasR++;
                        }
                    }
                }
                if (strlen($dr['BCCY']))
                {
                    foreach(unserialize($dr['BCCY']) as $y)
                    {
                        $y1 = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($y))->fetchArray();
                        if ($y1)
                        {
                            $mail->addBCC($y1['EMAIL'], sprintf("%s %s",$y1['LASTNAME'],$y1['FIRSTNAME']));
                            $hasR++;
                        }
                    }
                }
                if (strlen($dr['RECPZ']))
                {
                    $exp = explode(",",$dr['RECPZ']);
                    foreach($exp as $ext)
                    {
                        $xe = explode(" ",$ext);
                        $name = '';
                        $mail2 = '';
                        foreach($xe as $xx)
                        {
                            if (filter_var($xx, FILTER_VALIDATE_EMAIL))
                                $mail2 = $xx;
                            else
                                $name .= $xx.' ';
                        }
                        if (strlen($mail2))
                        {
                            $mail->addAddress($mail2,$name);
                            $hasR++;
                        }
                    }
                }
                if (strlen($dr['KOINZ']))
                {
                    $exp = explode(",",$dr['KOINZ']);
                    foreach($exp as $ext)
                    {
                        $xe = explode(" ",$ext);
                        $name = '';
                        $mail2 = '';
                        foreach($xe as $xx)
                        {
                            if (filter_var($xx, FILTER_VALIDATE_EMAIL))
                                $mail2 = $xx;
                            else
                                $name .= $xx.' ';
                        }
                        if (strlen($mail2))
                        {
                            $mail->addCC($mail2,$name);
                            $hasR++;
                        }
                    }
                }
                if (strlen($dr['BCCZ']))
                {
                    $exp = explode(",",$dr['BCCZ']);
                    foreach($exp as $ext)
                    {
                        $xe = explode(" ",$ext);
                        $name = '';
                        $mail2 = '';
                        foreach($xe as $xx)
                        {
                            if (filter_var($xx, FILTER_VALIDATE_EMAIL))
                                $mail2 = $xx;
                            else
                                $name .= $xx.' ';
                        }
                        if (strlen($mail2))
                        {
                            $mail->addBCC($mail2,$name);
                            $hasR++;
                        }
                    }
                }

		        $mail->Body = $message;
                if ($hasR > 0)
                {
                    $r =  $mail->send();
                    if ($r)
                        {
                            printf('Mail Sent OK.<br>');
                            $err = 0;
                        }
                    else
                        $err = 1;
                }
            }
            catch(phpmailerException $e)
            {
                printf($e->errorMessage().'<br>');
                $err = 1;
            }

            if ($err == 0)
            {
                // Move to send
                $newfid = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND SPECIALID = ?",array($dr['EID'],FOLDER_SENT))->fetchArray()['ID'];
                if ($newfid > 0)
                    QQ("UPDATE DOCUMENTS SET FID = ?,READSTATE = 0 WHERE ID = ?",array($newfid,$dr['ID']));
            }

            foreach($todel as $td)
                unlink($td);

        }
    }

    redirect($whereret);
    die;
}

PrintHeader();


echo '
<form method="POST" action="send.php" >
<input type="hidden" name="send" value="1" />';
$q1 = QQ("SELECT * FROM DOCUMENTS");
while($r1 = $q1->fetchArray())
{
    DocumentDecrypt($r1);
    $fid = $r1['FID'];
    $fr = FolderRow($fid);
    if (TopFolderType($fr['ID']) != FOLDER_OUTBOX)
        continue;

    if (UserAccessDocument($r1['ID'],$u->uid) != 2)
        continue;

    // Roles of the user
    if (!CanSend($r1['ID'],$u->uid))
        continue;

    $msg = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($r1['ID']))->fetchArray();
    if (!$msg)
        continue;
    DocumentDecrypt($msg);
    if (array_key_exists("ENCRYPTED",$r1))
        continue;
    if (array_key_exists("ENCRYPTED",$msg))
        continue;
    $x = sprintf('%s με θέμα: <b>%s</b>, %s [<a target="_blank" href="print.php?mid=%s&pdf=1">PDF</a>]<br>',$r1['TYPE'] == 1 ? "e-mail" : "Εγγραφο",$r1['TOPIC'],date("d/m/Y H:i",$msg['DATE']),$msg['ID']);
    $es = 0;
    if (strlen($r1['PROT']) < 1)
        {
            $x .= sprintf('<font color="red">Δεν υπάρχει πρωτόκολλο</font><br>');
            $es = 1;
        }
    else
    {
        $pr = (unserialize($r1['PROT']));
        $x .= sprintf("<b>Α.Π. %s</b> - %s<br>",$pr['n'],date("d/m/Y H:i",$pr['t']));

    }

    if ($msg['SIGNEDPDF'] == 0 || strlen($msg['SIGNEDPDF']) < 1)
    {
        $x .= sprintf('<font color="red">Δεν υπάρχει ψηφιακή υπογραφή</font><br>');
        $es = 1;
    }

    

    $jrecp = ReceipientArrayText($r1['ID']);
    if (count($jrecp))
    {
        $cnt = 1;
        foreach($jrecp as $r66)
        {
            $x .= sprintf("<b>%s.</b> %s<br>",$cnt++,$r66);
        }
    }
    else
    {
        $x .= sprintf('<font color="red">Δεν υπάρχουν παραλήπτες</font><br>');
        $es = 1;
    }

    $jrecp2 = KoinArrayText($r1['ID']);
    if (count($jrecp2))
    {
        $x .= 'Κοινοποιήσεις:<br>';
        $cnt = 1;
        foreach($jrecp2 as $r66)
        {
            $x .= sprintf("<b>%s.</b> %s<br>",$cnt++,$r66);
        }
    }

    $jrecp3 = BCCArrayText($r1['ID']);
    if (count($jrecp3))
    {
        $x .= 'Κρυφές Κοινοποιήσεις:<br>';
        $cnt = 1;
        foreach($jrecp3 as $r66)
        {
            $x .= sprintf("<b>%s.</b> %s<br>",$cnt++,$r66);
        }
    }


    
    $jrecp4 = EswArrayText($r1['ID']);
    if (count($jrecp3))
    {
        $x .= 'Εσωτερική Διανομή:<br>';
        $cnt = 1;
        foreach($jrecp4 as $r66)
        {
            $x .= sprintf("<b>%s.</b> %s<br>",$cnt++,$r66);
        }
    }

    if ($es)
    {
        printf('<input type="checkbox" name="ddd%s" /> ',$r1['ID']);
    }
    else
    {
        printf('<input type="checkbox" name="ddd%s" checked/> ',$r1['ID']);
    }
    echo $x;

    echo '<hr>';
}


echo '<button class="button is-primary autobutton">Αποστολή Επιλεγμένων</button></form><br><button href="eggr.php" class="autobutton button is-danger">Άκυρο</button>';
?>


    
