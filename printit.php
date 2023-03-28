<?php

function PrintLogotypo($eid,$did,$mid)
{
    $ep = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($eid))->fetchArray();
    if (!$ep)
        return "";
    $doc = DRow($did,1);
    if (!$doc)
        return "";
    $msg = MRow($mid,1);
    if (!$msg)
        return "";
    $issuer = UserRow($doc['UID']);
    $s = '';

    $top = '<img src="ed.png" width="32" height="32"><br>ΕΛΛΗΝΙΚΗ ΔΗΜΟΚΡΑΤΙΑ<br>';
    for($i = 9 ; $i >= 0 ; $i--)
    {
        $t = $ep["T$i"];
        if (!$t)
            continue;
        if (!strlen($t))
            continue;
        if ($i == 0)
            $top .= '<b>';
        $top .= $t;
        if ($i == 0)
            $top .= '</b>';
        $top .= '<br>';
    }

    $s .= $top;
    $s .= sprintf("<br>%s<br>%s %s<br><b>email: %s</b>",$ep['A1'],$ep['A2'],$ep['A3'],$ep['EMAIL']);


    if ($msg['INFO'] && strlen($msg['INFO']))
        $s .= sprintf("<br>Πληροφορίες: %s",$msg['INFO']);
    else
        $s .= sprintf("<br>Πληροφορίες: %s %s",$issuer ? $issuer['LASTNAME'] : '',$issuer ? $issuer['FIRSTNAME'] : '');
    $s .= sprintf("<br>Τηλ.: %s %s %s",$ep['TEL1'],$ep['TEL2'],$ep['TEL3']);
    return $s;
}

function NeedOE($did,$mid)
{
    $msg = MRow($mid,0);
    $q1 = QQ("SELECT * FROM MESSAGES WHERE DID = ? AND DATE < ? ORDER BY DATE DESC",array($did,$msg['DATE']));
    while($r1 = $q1->fetchArray())
    {
        if ($r1['SIGNEDPDF'] && strlen($r1['SIGNEDPDF']) > 5) 
        {
            return true;
        }
    }
    return false;
}


function PrintRight($eid,$did,$mid)
{
    global $defform;
    $ep = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($eid))->fetchArray();
    if (!$ep)
        return "";
    $doc = DRow($did,1);
    if (!$doc)
        return "";
    $msg = MRow($mid,1);
    if (!$msg)
        return "";
    
    $s = '<br><br>';
    $s .= ClassificationString($doc['CLASSIFIED'],1);
    $s .= '<br>';
    if (NeedOE($did,$mid))
        $s .= sprintf('<b>ΟΡΘΗ ΕΠΑΝΑΛΗΨΗ</b><br>');
    if ($doc['PRIORITY']  > 0)
        $s .= sprintf('<b>%s</b><br>',PriorityString($doc['PRIORITY']));
    if ($doc['DUEDATE'] && strlen($doc['DUEDATE']))
        $s .= sprintf('ΔΙΕΚΠ. ΜΕΧΡΙ: <b>%s</b><br>',date("d/m/Y",$doc['DUEDATE']));
    if ($doc['PROT'] && strlen($doc['PROT']))
        {
        $pr = (unserialize($doc['PROT']));
        $s .= sprintf('%s, Α.Π. %s &mdash; %s',$ep['A3'],$pr['n'],date("d/m/Y H:i",$pr['t']));
        }

    $fmt = $defform;
    if ($doc['FORMATTING'])
        $fmt = unserialize($doc['FORMATTING']);

    if ($fmt['form_recp'] == 1)
        $s .= '<br><br><br>ΠΡΟΣ: <b>Πίνακα Αποδεκτών</b>   <br>';

    // Receipients 
    $rr = ReceipientArrayText($did);
    if (count($rr) && $fmt['form_recp'] == 0)
    {
        $cnx = 1;
        $s .= '<br><br><br>ΠΡΟΣ:<br>';

        $cnx = 1;
        foreach($rr as $r)
        {
            $s .= sprintf("<b>%s.</b> %s<br>",$cnx,$r);
            $cnx++;
        }
    }

    // Receipients 
    $rr = KoinArrayText($did);
    if (count($rr) && $fmt['form_recp'] == 0)
    {
        $cnx = 1;
        $s .= '<br>KOIN:<br>';

        $cnx = 1;
        foreach($rr as $r)
        {
            $s .= sprintf("<b>%s.</b> %s<br>",$cnx,$r);
            $cnx++;
        }
    }

    return $s;
}


function titsig($signer,$docr,$what = 0)
{
    $tit = $signer['TITLE'];
    if ($docr['SIGNERTITLES'] && strlen($docr['SIGNERTITLES']))
        $tit = explode(",",$docr['SIGNERTITLES'])[$what];
    if (trim($tit) == '')
        return sprintf("<b>%s %s</b><br><br>",$signer['LASTNAME'],$signer['FIRSTNAME']);
    else
        return sprintf("%s<br><br><b>%s %s</b><br><br>",$tit,$signer['LASTNAME'],$signer['FIRSTNAME']);
    }




function PrintSignature($docrow)
{
    $eid = $docrow['EID'];
    $ep = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($eid))->fetchArray();
    if (!$ep)
        return "";

    $role = QQ("SELECT * FROM ROLES WHERE ROLEID = ? AND EID = ?",array(ROLE_SIGNER0,$eid))->fetchArray();
    if (!$role)
        return '';
    $signer = UserRow($role['UID']);
    $a = titsig($signer,$docrow);

    // Extra
    $ji = 1;
    if ($docrow['ADDEDSIGNERS'])
    {
        $extrasigs = explode(",",$docrow['ADDEDSIGNERS']);
        $extrasigs [] = $role['UID'];

        // Sort them proist
        $extrasigs = SortArrayProist($extrasigs);

        $down = 0;
        foreach($extrasigs as $uuid)
        {
            $signer2 = UserRow($uuid);
            if ($uuid == $role['UID'])
                {
                    $down = 1;
                    continue;
                }

            if ($down == 0)
                $a = titsig($signer2,$docrow,$ji).$a;
            else
                $a = $a.titsig($signer2,$docrow,$ji);
            $ji++;
        }
    }


    return $a;
}

function PrintAttachments($doc,$msg,$mid)
{
    $s = '';
    $q = QQ("SELECT * FROM ATTACHMENTS WHERE MID = ?",array($mid));
    $cnt = 0;

    while($r = $q->fetchArray())
    {
        if ($cnt == 0)
            $s .= sprintf("Συννημένα<hr>");
        $cnt++;
        if ($doc['CLASSIFIED'] > 0)
        {
            $pwd = PasswordFromSession($doc['ID']);
            if ($pwd !== FALSE)
                $s .= sprintf("<b>%s.</b> %s<br>",$cnt,ed($r['DESCRIPTION'],$pwd,'d'));

        }
        else
           $s .= sprintf("<b>%s.</b> %s<br>",$cnt,$r['DESCRIPTION']);
    }
    if (strlen($s))
        $s .= '<br>';
    return $s;
}

function PrintEsw($doc)
{
  // Receipients 
  $rr = EswArrayText($doc['ID']);
  $s = '';
  if (count($rr))
  {
      $s = 'Εσωτερική διανομή:<hr>';
      $cnx = 1;

      $cnx = 1;
      foreach($rr as $r)
      {
          $s .= sprintf("<b>%s.</b> %s<br>",$cnx,$r);
          $cnx++;
      }
  }


  return $s;
}



function PrintRT($doc)
{
    global $defform;
    $fmt = $defform;
    if ($doc['FORMATTING'])
        $fmt = unserialize($doc['FORMATTING']);

    if ($fmt['form_recp'] != 1)
        return;

        $s = '';
    $did = $doc['ID'];

  // Receipients 
  $rr = ReceipientArrayText($did);
  $cnx = 1;
  if (count($rr))
  {
      $cnx = 1;
      $s .= '<b>Παραλήπτες</b>:<hr>';

      $cnx = 1;
      foreach($rr as $r)
      {
          $s .= sprintf("<b>%s.</b> %s<br>",$cnx,$r);
          $cnx++;
      }
      $s .= '<br>';
  }

  // Receipients 
  $rr = KoinArrayText($did);
  if (count($rr))
  {
      $cnx = 1;
      $s .= '<b>Κοινοποιήσεις</b>:<hr>';

      $cnx = 1;
      foreach($rr as $r)
      {
          $s .= sprintf("<b>%s.</b> %s<br>",$cnx,$r);
          $cnx++;
      }
      $s .= '<br>';
  }

    return $s;
}

function PrintAll($did,$mid)
{
    $doc = DRow($did,1);
    if (!$doc)
        return "";
    $msg = MRow($mid,1);
    if (!$msg)
        return "";
    if ($doc['TYPE'] == 1)
        return $msg['MSG'];
        
    $right = '';
    $right = PrintRight($doc['EID'],$doc['ID'],$msg['ID']);
    $s = "";
    $s .=  '<div class="content" style="margin:20px">';
    $logot = PrintLogotypo($doc['EID'],$doc['ID'],$msg['ID']);
    $s .= sprintf('<table width="100%%">');
    $s .= sprintf('<tr>');
    $s .= sprintf('<td width="50%%" style="text-align: center;">%s</td>',$logot);
    $s .= sprintf('<td width="50%%" style="text-align: center;">%s</td>',$right);
    $s .= sprintf('</tr>');
    $s .= sprintf('</table><br><br>');
    if ($doc['CATEGORY'] == 1) $s .= sprintf('<div style="text-align: center;">ΑΠΟΦΑΣΗ</div><br>');
    if ($doc['CATEGORY'] == 2) $s .= sprintf('<div style="text-align: center;">ΣΥΜΒΑΣΗ</div><br>');
    if ($doc['CATEGORY'] == 3) $s .= sprintf('<div style="text-align: center;">ΛΟΓΑΡΙΑΣΜΟΣ</div><br>');
    if ($doc['CATEGORY'] == 4) $s .= sprintf('<div style="text-align: center;">ΑΝΑΚΟΙΝΩΣΗ</div><br>');
    $s .= sprintf("Θέμα: <b>%s</b><br>",$doc['TOPIC']);
    if ($doc['RELATED'] && strlen($doc['RELATED']))
    {
        $rels = explode(",",$doc['RELATED']);
        $s .= sprintf("Σχετ: ");
        $numsx = 1;
        foreach($rels as $rel)
        {
            $doc1 = DRow(abs($rel),1);
            $msg1 = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array(abs($rel)))->fetchArray();
            if ($numsx > 1)
                $s .= ", ";
            $s .= sprintf("%s. <b>%s</b> (%s)",$numsx,$doc1['TOPIC'],date("j/m/Y",$msg1['DATE']));
            if ($doc1['SHDEPROTOCOL'] && strlen($doc1['SHDEPROTOCOL']))
                $s .= sprintf(" (%s)",$doc1['SHDEPROTOCOL']);
            $numsx++;
        }
        $s .= '<br>';
    }
    $s .= '<hr>';
    $s .= sprintf("%s",$msg['MSG']);
    $s .= sprintf('<table width="100%%">');
    $s .= sprintf('<tr>');
    $s .= sprintf('<td width="50%%" style="text-align: center;">%s</td>',"");
    $s .= sprintf('<td width="50%%" style="text-align: center;">%s</td>',PrintSignature($doc));
    $s .= sprintf('</tr>');
    $s .= sprintf('</table>');
    $s .= PrintRT($doc);
    $s .= PrintAttachments($doc,$msg,$msg['ID']);
    $s .= PrintEsw($doc);
    if ($doc['ORIGINALITY'] == 1)
        $s .= '<br>ΑΚΡΙΒΕΣ ΑΝΤΙΓΡΑΦΟ';

    return $s;
}
