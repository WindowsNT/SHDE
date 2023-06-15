<?php

require_once "functions.php";
require_once "printit.php";
require_once "pdfstuff.php";

if (array_key_exists("clsid",$req))
{
    $doc2 = QQ("SELECT * FROM DOCUMENTS WHERE CLSID = ?",array($req['clsid']))->fetchArray();
    if ($doc2)
    {
        $msg2 = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($doc2['ID']))->fetchArray();
        $req['mid'] = $msg2['ID'];
        $req['pdf'] = 1;
    }
}
else
{
    if (!$u)
        diez(); 
}

if (array_key_exists("zip",$req))
{
    $zipf = tempnam(sys_get_temp_dir(),"pdff");
    $zip= new ZipArchive;
    if ($zip->open($zipf, ZipArchive::CREATE)!==TRUE) {
        exit("cannot open <$filename>\n");
    }
    foreach(explode(",",$req['docs']) as $doc)
    {
        $doc2 = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($doc))->fetchArray();
        if ($doc2)
        {
            $msg2 = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($doc2['ID']))->fetchArray();
            $mid =  $msg2['ID'];
        }
        $e = PrintAll($doc,$mid);
        $eidr = EPRow($doc2['EID']);
        $e2 = PDFConvert($eidr['NAME'],$doc2['TOPIC'],$e,$doc2['CLSID'],$msg2['DATE'],$doc2['PDFPASSWORD'] ? $doc2['PDFPASSWORD'] : '');
        $zip->addFromString($doc.".pdf",$e2);
    }
    $zip->close();
    header("Content-type: application/zip");
    readfile($zipf);
    die;
}

$msg = MRow($req['mid'],1);
if (!$msg)
    diez();

$doc = DRow($msg['DID'],1);
if (!$doc)
    diez();

if (array_key_exists("pdf",$req) || array_key_exists("zip",$req) || $doc['TYPE'] == 2)
{
}
else
    require_once "output.php";

            
QQ("UPDATE DOCUMENTS SET READSTATE = 0 WHERE ID = ?",array($doc['ID']));

if ($doc['TYPE'] == 2)
{
    $q1 = QQ("SELECT * FROM ATTACHMENTS WHERE MID = ?",array($msg['ID']))->fetchArray();
    if ($q1)
    {
        header(sprintf("Content-Type: %s",$q1['TYPE']));
        $bi = GetBinary("ATTACHMENTS","DATA",$q1['ID']);
        echo $bi;
        die;
    }
    header(sprintf("Content-Type: %s",$msg['MIME']));
    if ($msg['MIME'] != "application/pdf")
        {
            $ext = mime_type($msg['MIME'],1);
            if ($ext == "")
                $ext = 'bin';
            header(sprintf('Content-Disposition: attachment; filename="%s.%s"',$doc['ID'],$ext));
        }

    $bi = GetBinary("MESSAGES","SIGNEDPDF",$msg['ID']);
    echo $bi;
    die;
}

if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
    {
        if (array_key_exists("pwd",$req))
            {
                $_SESSION[sprintf('shde_pwd_%s',$doc['ID'])] = $req['pwd'] ;
            
                $msg = MRow($req['mid'],1);
                if (!$msg)
                    diez();
                
                $doc = DRow($msg['DID'],1);
                if (!$doc)
                    diez();
            }
    }

$eidr = EPRow($doc['EID']);

if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
{
    redirect(sprintf("decrypt.php?did=%s",$doc['ID']));
    die;
}

if ($u && UserAccessMessage($req['mid'],$u->uid) == 0)
    diez();





$s = PrintAll($doc['ID'],$msg['ID']);

if (array_key_exists("pdf",$req))
{
    if ($msg['SIGNEDPDF'] && strlen($msg['SIGNEDPDF']) > 5 && $doc['FROMX'] && strlen($doc['FROMX']) && $msg['MIME'] && strlen($msg['MIME']))
        {
            header("Content-type: {$msg['MIME']}");    
        }
    else
        header("Content-type: application/pdf");    
    if (array_key_exists("download",$req))
        header(sprintf('Content-Disposition: attachment; filename="%s-%s.pdf"',$doc['ID'],$msg['ID']));

    if ($msg['SIGNEDPDF'] && strlen($msg['SIGNEDPDF']) > 5)
    {
        $bi = GetBinary("MESSAGES","SIGNEDPDF",$msg['ID']);
        if ($doc['CLASSIFIED'] > 0)
        {
            $pwd = PasswordFromSession($doc['ID']);
            if ($pwd !== FALSE)
                $bi = ed($bi,$pwd,'d');
        }
        echo $bi;
    }
    else
        echo PDFConvert($eidr['NAME'],$doc['TOPIC'],$s,$doc['CLSID'],$msg['DATE'],$doc['PDFPASSWORD'] ? $doc['PDFPASSWORD'] : '');
}
else
    echo $s;