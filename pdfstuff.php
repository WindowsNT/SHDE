<?php


require_once "vendor/autoload.php";

function PDFConvert2($author,$title,$htmls,$clsid = "")
{
    // create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 001');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 14, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

// Set some content to print
$html = <<<EOD
 <b>Hello</b>
EOD;

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
return $pdf->Output('example_001.pdf', 'S');
}

function PDFConvert($author,$title,$htmls,$clsid,$ts = 0,$pwd = '')
{
    //    return PDFConvert2($author,$title,$htmls,$clsid);
    // Generate the PDF
/*    copy("calibri.ctg.z","vendor/tecnickcom/tcpdf/fonts/calibri.ctg.z");
    copy("calibri.z","vendor/tecnickcom/tcpdf/fonts/calibri.z");
    copy("calibri.php","vendor/tecnickcom/tcpdf/fonts/calibri.php");
    copy("calibrib.ctg.z","vendor/tecnickcom/tcpdf/fonts/calibrib.ctg.z");
    copy("calibrib.z","vendor/tecnickcom/tcpdf/fonts/calibrib.z");
    copy("calibrib.php","vendor/tecnickcom/tcpdf/fonts/calibrib.php");
    copy("calibrii.ctg.z","vendor/tecnickcom/tcpdf/fonts/calibrii.ctg.z");
    copy("calibrii.z","vendor/tecnickcom/tcpdf/fonts/calibrii.z");
    copy("calibrii.php","vendor/tecnickcom/tcpdf/fonts/calibrii.php");
  */  
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', true,false);

    $fontname = TCPDF_FONTS::addTTFfont('calibri.ttf', 'TrueTypeUnicode', '', 96);
    $fontname = TCPDF_FONTS::addTTFfont('calibrib.ttf', 'TrueTypeUnicode', '', 96);
    $fontname = TCPDF_FONTS::addTTFfont('calibrii.ttf', 'TrueTypeUnicode', '', 96);
    $fontname = TCPDF_FONTS::addTTFfont('calibrili.ttf', 'TrueTypeUnicode', '', 96);
    $fontname = TCPDF_FONTS::addTTFfont('calibril.ttf', 'TrueTypeUnicode', '', 96);
    $fontname = TCPDF_FONTS::addTTFfont('calibriz.ttf', 'TrueTypeUnicode', '', 96);

    $pdf->setHeaderData('',0,'','',array(0,0,0), array(255,255,255) );  
    $pdf->setFooterData('',0,'','',array(0,0,0), array(255,255,255) );  
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);

    $pdf->setDocCreationTimestamp($ts);
    $pdf->setDocModificationTimestamp($ts);

    // SADES Not supporting encrypted documents yet
//    if ($pwd != '')
  //      $pdf->SetProtection(array('print', 'copy'), $pwd, null, 0, null);
    
    $pdf->SetCreator($author);
    $pdf->SetAuthor($author);
    $pdf->SetTitle($title);
    $pdf->SetSubject($title);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
//    $pdf->setPrintHeader(true);
//    $pdf->setPrintFooter(true);
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetKeywords($clsid);
//    $fontname = TCPDF_FONTS::addTTFfont('calibri.ttf', 'TrueTypeUnicode', '', 96);
  //  $fontname = TCPDF_FONTS::addTTFfont('calibrib.ttf', 'TrueTypeUnicode', '', 96);ad
   $pdf->SetFont("calibri", '', 12);
//    $pdf->SetFont("freesans", '', 12);
    $pdf->AddPage();
    $pdf->writeHTML($htmls, true, false, true, false, '');
    $pdf->lastPage();

    $pdfstr = $pdf->Output('1.pdf', 'S');
    return $pdfstr;
}
