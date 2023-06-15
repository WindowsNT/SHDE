<?php

require_once "functions.php";
if (!$u)
    diez();
$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

if (array_key_exists("fifiz",$_FILES))
{
    require_once "vendor/autoload.php";
 
    if ($_FILES['fifiz']['type'] != "application/x-zip-compressed")
        die("A PDF file is required");
    if (strlen($_FILES['fifiz']['tmp_name']) == 0)
        die("A PDF file is required");
    if (strlen($_FILES['fifiz']['size']) == 0)
        die("A PDF file is required");

    $zipFile = $_FILES['fifiz']['tmp_name'];
    $zip = new ZipArchive;
    if ($zip->open($zipFile) !== true)
        die("Zip Error");

    for( $i = 0; $i < $zip->numFiles; $i++ ){ 
        $stat = $zip->statIndex( $i ); 
        $statn = $stat['name'];

        foreach(explode(",",$_POST['docs']) as $doc)
        {
            if($doc.".pdf" == $statn)
            {
                $doc2 = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($doc))->fetchArray();
                $msg2 = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($doc2['ID']))->fetchArray();
                $rd = file_get_contents("zip://$zipFile#$statn");

                // Check if a signature is there    
                if (strpos($rd, "adbe.pkcs7.detached") === false && strpos($rd,"ETSI.CAdES.detached") === false) 
                    die("Δεν βρέθηκε ψηφιακή υπογραφή στο έγγραφο.");

                // Check if it's the same PDF
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseContent($rd);
                $details = $pdf->getDetails();
                if (!array_key_exists("Keywords",$details))
                    die("Το αρχείο αυτό δεν είναι ψηφιακή υπογραφή του αρχικού αρχείου.");
                if ($details['Keywords'] != $doc2['CLSID'])
                    die("Το αρχείο αυτό δεν είναι ψηφιακή υπογραφή του αρχικού αρχείου.");
                

                if ($doc2['CLASSIFIED'] > 0)
                {
                    $pwd = PasswordFromSession($doc2['ID']);
                    if ($pwd === FALSE)
                    {
                            die("Το αρχείο αυτό δεν μπορεί να αποθηκευτεί χωρίς κωδικό");
                        }
                    $rd = ed($rd,$pwd,'e');
                }
                QQ("UPDATE MESSAGES SET SIGNEDPDF = ? WHERE ID = ?",array($rd,$msg2['ID']));                 
                print("Το ψηφιακά υπογεγραμμένο έγγραφο καταχωρήθηκε επιτυχώς!<br>");

                break;
            }
        }
    }
    die;
}

if (array_key_exists("fifi",$_FILES))
{
    require_once "vendor/autoload.php";
 
    if ($_FILES['fifi']['type'] != "application/pdf")
        die("A PDF file is required");
    if (strlen($_FILES['fifi']['tmp_name']) == 0)
        die("A PDF file is required");
    if (strlen($_FILES['fifi']['size']) == 0)
        die("A PDF file is required");

    $msg = MRow($_POST['did'],1);
    if (!$msg)
        die("Το μήνυμα δεν βρέθηκε.");
    $doc = DRow($msg['DID'],1);
    if (!$doc)
        die("Το μήνυμα δεν βρέθηκε.");

    $rd = file_get_contents($_FILES['fifi']['tmp_name']);

    // Check if a signature is there    
    if (strpos($rd, "adbe.pkcs7.detached") === false && strpos($rd,"ETSI.CAdES.detached") === false) 
        die("Δεν βρέθηκε ψηφιακή υπογραφή στο έγγραφο.");

    // Check if it's the same PDF
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseContent($rd);
    $details = $pdf->getDetails();
    if (!array_key_exists("Keywords",$details))
        die("Το αρχείο αυτό δεν είναι ψηφιακή υπογραφή του αρχικού αρχείου.");
    if ($details['Keywords'] != $doc['CLSID'])
        die("Το αρχείο αυτό δεν είναι ψηφιακή υπογραφή του αρχικού αρχείου.");
    

    if ($doc['CLASSIFIED'] > 0)
    {
        $pwd = PasswordFromSession($doc['ID']);
        if ($pwd === FALSE)
        {
                die("Το αρχείο αυτό δεν μπορεί να αποθηκευτεί χωρίς κωδικό");
            }
        $rd = ed($rd,$pwd,'e');
    }
    QQ("UPDATE MESSAGES SET SIGNEDPDF = ? WHERE ID = ?",array($rd,$msg['ID'])); 
    
    print("Το ψηφιακά υπογεγραμμένο έγγραφο καταχωρήθηκε επιτυχώς!");
    printf('<br><hr><a href="sign.php?docs=%s">Eπιστροφή</a>',$_POST['docs']);
    die;
}
    
require_once "output.php";
PrintHeader($whereret);


$docs = explode(",",$req['docs']);

printf('<a href="https://webapp.mindigital-shde.gr/login" target="_blank">Εφαρμογή ΣΗΔΕ ΥΨηΔ</a><hr>');

$clsids = array();
foreach($docs as $docid)
{
   $doc = DRow($docid,1);
    if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
        continue;
    $msg = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($doc['ID']))->fetchArray();
    if (!$msg)
        continue;
    DocumentDecrypt($msg);

    if (CanSign($docid,$u->uid) != 0) 
        continue;

    $anyp = '';
    $anypp = sprintf('shde_pwd_%s',$doc['ID']);
    if (array_key_exists($anypp,$_SESSION))
        $anyp = $_SESSION[$anypp];
    
    $clsids[] = $doc['CLSID'];
    printf('<div class="card"><div class="content" style="margin: 20px;"><br><br>Έγγραφο με θέμα: <b>%s</b>, %s<br> [%s] <br> [<a target="_blank" href="print.php?mid=%s&pdf=1&download=1">Κατέβασμα αρχικού PDF</a>]  
            <form action="sign.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="did" value="%s" />
            <input type="hidden" name="docs" value="%s" />
            <input type="file" name="fifi" accept=".pdf" required>
            <button class="button is-small is-success">Υποβολή</button>
            </form>
            <br>
            </div>
            </div>
        
        
        ',$doc['TOPIC'],date("d/m/Y H:i",$msg['DATE']),BuildSadesRequest($doc['ID']),$msg['ID'],$doc['ID'],$req['docs']);
    
}

echo 'Μαζική Υπογραφή εγγράφων<hr>';

printf('<a href="print.php?docs=%s&zip=1">Κατέβασμα ZIP με PDFs</a><br>
Ανέβασμα  ZIP με υπογραφές:<br>
<form action="sign.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="docs" value="%s" />
<input type="file" name="fifiz" accept=".zip" required>
<button class="button is-small is-success">Υποβολή</button>
</form>
<br>
</div>
</div>


',$req['docs'],$req['docs']);
