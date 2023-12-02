<?php

function DocTR($did,$a,$full = 0,$putoid = 1,$puteid = 1,$putfid = 1)
{
    global $req;
    global $u;
    $showtopic = 1; if (array_key_exists("shde_hide_topic",$_SESSION) && $_SESSION['shde_hide_topic'] == 1) $showtopic = 0;
    $showwriter = 1; if (array_key_exists("shde_hide_writer",$_SESSION) && $_SESSION['shde_hide_writer'] == 1) $showwriter = 0;
    $showfolder = 1; if (array_key_exists("shde_hide_folder",$_SESSION) && $_SESSION['shde_hide_folder'] == 1) $showfolder = 0;
    $showcat = 1; if (array_key_exists("shde_hide_cat",$_SESSION) && $_SESSION['shde_hide_cat'] == 1) $showcat = 0;
    $showfor = 1; if (array_key_exists("shde_hide_for",$_SESSION) && $_SESSION['shde_hide_for'] == 1) $showfor = 0;
    $showep = 1; if (array_key_exists("shde_hide_ep",$_SESSION) && $_SESSION['shde_hide_ep'] == 1) $showep = 0;
    $showclass = 1; if (array_key_exists("shde_hide_class",$_SESSION) && $_SESSION['shde_hide_class'] == 1) $showclass = 0;
    $showpri = 1; if (array_key_exists("shde_hide_priority",$_SESSION) && $_SESSION['shde_hide_priority'] == 1) $showpri = 0;
    $showsx = 1; if (array_key_exists("shde_hide_sxetika",$_SESSION) && $_SESSION['shde_hide_sxetika'] == 1) $showsx = 0;
    $showatt = 1; if (array_key_exists("shde_hide_att",$_SESSION) && $_SESSION['shde_hide_att'] == 1) $showatt = 0;
    $showcomm = 1; if (array_key_exists("shde_hide_comments",$_SESSION) && $_SESSION['shde_hide_comments'] == 1) $showcomm = 0;

$showatt = 1;

    if (is_array($did))
    {
        $r1 = $did;
        $did = $r1['ID'];
    }
    else
        $r1 = DRow($did,1);
    if (!$r1)
        return "";
    $s ='';
    $s .= sprintf('<tr id="doc%s">',$r1['ID']);
    $s .= sprintf('<td>%s <input style="display: inline-block !important;" type="checkbox" id="check%s" class="checking"> ',$r1['ID'],$r1['ID']);

    $fill = '#000000';
    if (array_key_exists("ENCRYPTED",$r1) && $r1['ENCRYPTED'] == 1)
        $fill = '#FF0000';
    else
    if ($r1['CLASSIFIED'] > 0)
        $fill = '#00FF00';
    else
        $fill = $r1['COLOR'];
    if ($fill == '')
        $fill = '#000000';

    $s .= sprintf('<svg style="display: inline-block;" width="15" height="15">
    <rect x="0" y="0" width="15" height="15" fill="%s" />
    </svg>',$fill);

    $inc = IsIncoming($r1);

    $tx = $r1['TOPIC'];
    if ($inc)
    {
        $tx = sprintf('<a href="print.php?clsid=%s"  title="Προβολή εγγράφου" target="_blank">%s</a>',$r1['CLSID'],$r1['TOPIC']);
    }
    else
    if (!strstr($tx,"<a"))
    {
//        $tx = sprintf('<a href="doc.php?did=%s" >%s</a>',$r1['ID'],$r1['TOPIC']);
        $tx = sprintf('<a href="print.php?clsid=%s" title="Προβολή εγγράφου" target="_blank">%s</a>',$r1['CLSID'],$r1['TOPIC']);
    }

    if ($showtopic)
    {        
        if ($r1['READSTATE'] == 1)
            $s .= sprintf("<td><b>%s</b></td>",$tx);
        else
            $s .= sprintf("<td>%s</td>",$tx);
    }

    $sent = TopFolderType($r1['FID']) == FOLDER_SENT;
    if ($inc || $sent || $a != 2)
        $sxet = '';
    else
        $sxet = sprintf('<button class="autobutton button is-smaller is-link" href="related.php?did=%s">Επιλογή</button><hr>',$r1['ID']);
    if ($r1['RELATED'] && strlen($r1['RELATED']))
    {
        foreach(explode(",",$r1['RELATED']) as $relid)
        {
            $dr3 = DRow(abs($relid),true);
            $sxet .= sprintf('%s. %s<br>',$dr3['ID'],$dr3['TOPIC']);
        }
    }
    
    
    global $u;
    $ur = UserRow($u->uid);
    $u1 = UserRow($r1['UID']);

    if ($showwriter)
    {
        if (!$u1)
            {
                $ft = FromArrayText($did);
                $ft2 = '';
                foreach($ft as $f)
                    {
                                       $ft2 .= $f;
                        $ft2 .= '<br>';
                    }
                $s .= sprintf("<td>%s</td>",$ft2);
            }
        else
        if ($r1['READSTATE'] == 1)
            $s .= sprintf("<td><b>%s %s</b></td>",$u1['LASTNAME'],$u1['FIRSTNAME']);
        else
            $s .= sprintf("<td>%s %s</td>",$u1['LASTNAME'],$u1['FIRSTNAME']);
    }

    $f1 = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($r1['FID']))->fetchArray();
    $topfoldertype = TopFolderType($r1['FID']);
    if ($putfid && $showfolder && $f1)
    {
        if ($f1['SPECIALID'] == FOLDER_OUTBOX)
        {
            if ($r1['READSTATE'] == 1)
                $s .= sprintf('<td><b><font color="red">%s</font></b>',$f1['NAME']);
            else
                $s .= sprintf('<td><font color="red">%s</font>',$f1['NAME']);

        }
        else
        {
            if ($r1['READSTATE'] == 1)
                $s .= sprintf('<td><b>%s</b>',$f1['NAME']);
            else
                $s .= sprintf('<td>%s',$f1['NAME']);
        }
        
        $s .= '</td>';
    }

    if ($showcat)
        $s .= sprintf("<td>%s</td>",CategoryFromID($r1['CATEGORY']));

    $e1 = EPRow($r1['EID']);
    if ($putoid && $showfor)
    {
        $f1 = FRow($e1['OID']);
        if ($r1['READSTATE'] == 1)
            $s .= sprintf("<td><b>%s</b></td>",$f1['NAME']);
        else
            $s .= sprintf("<td>%s</td>",$f1['NAME']);
    }
    if ($puteid && $showep)
    {
        if ($r1['READSTATE'] == 1)
            $s .= sprintf("<td><b>%s</b></td>",$e1['NAME']);
        else
            $s .= sprintf("<td>%s</td>",$e1['NAME']);
    }
    if ($ur['CLASSIFIED'] > 0 && $showclass)
        $s .= sprintf("<td>%s</td>",ClassificationString($r1['CLASSIFIED'],1));

    if ($showpri)
    {
        $s .= sprintf("<td>%s",PriorityString($r1['PRIORITY'],1));
        if ($r1['DUEDATE'] != "" && $r1['DUEDATE'] != 0) 
            $s .= sprintf('<br><b>Μέχρι <b>%s</b>',date("d/m/Y",$r1['DUEDATE']));
        $s .= '</td>';
    }

    if ($showsx)
        $s .= sprintf("<td>%s</td>",$sxet);
    
    if ($showcomm)
        {
            if ($inc || $a != 2)
                $scomm = sprintf('');
            else
                $scomm = sprintf('<button class="autobutton button is-smaller is-link" href="comments.php?did=%s">Επιλογή</button><hr>',$r1['ID']);
            $s .= sprintf("<td>%s</td>",$scomm);
        }

    if ($r1['PROT'] == NULL || !strlen($r1['PROT']))
       {
        if ($inc == 0)
           $s .= sprintf('<td><a href="javascript:prot(%s,%s,%s,%s);">Πάρε</a></td>',$req['oid'],$req['eid'],$req['fid'],$r1['ID']);
        else
            $s .= sprintf('<td></td>');
    }
    else
    {
        $pr = (unserialize($r1['PROT']));
        $s .= sprintf("<td><b>%s</b><br>%s",$pr['n'],date("d/m/Y H:i",$pr['t']));
        if ($r1['SHDEPROTOCOL'] && strlen($r1['SHDEPROTOCOL']))
            $s .= sprintf('<br><b>ΚΣΗΔΕ</b>: %s<br>%s',$r1['SHDEPROTOCOL'],date("d/m/Y H:i",$r1['SHDEPROTOCOLDATE']));
        $s .= '</td>';
    }


    $jrecp = ReceipientArrayText($did);
    $s .= sprintf("<td>");


    if ($inc == 0 && $sent == 0 && $a == 2)
        $s .= sprintf('<button class="autobutton is-smaller button is-link" href="recp.php?did=%s">Επιλογή</button><hr>',$r1['ID']);
    if (count($jrecp))
    {
        $cnt = 1;
        $s .= '<div class="table-container"  style="max-height: 300px;  overflow: auto;"><table class="table table is-fullwidth"><thead><tr><th>#</th><th>Προς</th></thead><tbody>';
        foreach($jrecp as $r66)
        {
            $s .= sprintf("<tr><td><b>%s</b></td><td>%s</td>",$cnt++,$r66);
        }
        $s .= '</table></div>';
    }

    $jkoin = KoinArrayText($did);

    if (count($jkoin))
    {
        $cnt = 1;
        $s .= '<div class="table-container"  style="max-height: 300px;  overflow: auto;"><table class="table table is-fullwidth"><thead><tr><th>#</th><th>Κοινοποίηση</th></thead><tbody>';
        foreach($jkoin as $r66)
        {
            $s .= sprintf("<tr><td><b>%s</b></td><td>%s</td>",$cnt++,$r66);
        }
        $s .= '</table></div>';
    }

    
    $jkoin2 = BCCArrayText($did);
    if (count($jkoin2))
    {
        $cnt = 1;
        $s .= '<div class="table-container"  style="max-height: 300px;  overflow: auto;"><table class="table table is-fullwidth"><thead><tr><th>#</th><th>Κρ. Κοινοποίηση</th></thead><tbody>';
        foreach($jkoin2 as $r66)
        {
            $s .= sprintf("<tr><td><b>%s</b></td><td>%s</td>",$cnt++,$r66);
        }
        $s .= '</table></div>';
    }

    $jkoin3 = EswArrayText($did);
    if (count($jkoin3))
    {
        $cnt = 1;
        $s .= '<div class="table-container"  style="max-height: 300px;  overflow: auto;"><table class="table table is-fullwidth"><thead><tr><th>#</th><th>Εσ. Διανομή</th></thead><tbody>';
        foreach($jkoin3 as $r66)
        {
            $s .= sprintf("<tr><td><b>%s</b></td><td>%s</td>",$cnt++,$r66);
        }
        $s .= '</table></div>';
    }

    if ($inc == 0)
    {
        if ($r1['SHDEPROTOCOL'] && strlen($r1['SHDEPROTOCOL']))
        {
            if ($r1['SHDECHECKSENT'] && strlen($r1['SHDECHECKSENT']))
            {
                $j = json_decode($r1['SHDECHECKSENT']);
                $one = 0;
                foreach($j->results as $jj)
                {    
                    if (strlen($jj->LocalProtocolNo))
                        {
                            $s .= sprintf('<br>[%s,%s]<br>%s<br>%s<br>%s<br>',$jj->SectorCode,(int)$jj->DepartmentCode,date("j/m/Y H:i",(int)strtotime($jj->DateChanged)),$jj->LocalProtocolNo,$jj->Comments);
                            $one++;
                        }
                }
            }
            else
            {
            }
        }
        if ($sent)
            $s .= sprintf('<br><a href="checksent.php?docs=%s">Έλεγχος</a>',$did);
    }


    $s .= sprintf("</td>");


    $s .= sprintf("<td>");
    $q2 = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($r1['ID']));
    $lastmid = 0;
    $cnt = 0;
    $firstmid = 0;
    $hasDS = 0;
    while($r2 = $q2->fetchArray())
    {
        DocumentDecrypt($r2);
        $bu = '<article class="panel is-primary">
            <p class="panel-heading">
            %s
            </p>
            <div class="panel-block">
            <p>
               <div class="content"> %s </div>
            </p>
            </div>
            <p class="panel-block">
            %s %s
            </a>
        </article>';
        if ($cnt == 0)
            $firstmid = $r2['ID'];
        $lastmid = $r2['ID'];

/*        $s2 = sprintf('&nbsp; &nbsp; <a target="_blank" href="print.php?mid=%s">Εκτύπωση</a> &mdash; <a target="_blank" href="print.php?mid=%s&pdf=1">PDF</a>',$lastmid,$lastmid);
        if ($lastmid > 0 && $a == 2 && $cnt == 0)
        {
            $s2 .= sprintf(' &mdash; <a href="neweggr.php?did=%s&mid=%s">Επεξεργασία</a>',$r1['ID'],$lastmid);
            $s2 .= sprintf(' &mdash; <a href="recp.php?did=%s&mid=%s">Παραλήπτες</a>',$r1['ID'],$lastmid);
            $s2 .= sprintf(' &mdash; <a href="att.php?did=%s&mid=%s">Επισυναπτόμενα</a>',$r1['ID'],$lastmid);
            $s2 .= sprintf(' &mdash; <a href="javascript:delmid(%s);">Διαγραφή έκδοσης</a>',$lastmid);
        }
*/
        $s2 = '';
/*        $s2 = sprintf('<div class="dropdown is-hoverable">
        <div class="dropdown-trigger">
        <a aria-haspopup="true" aria-controls="dropdown-menu">
        <span>Εκτυπώσεις</span>
          </a>
        </div>
        <div class="dropdown-menu" role="menu">
          <div class="dropdown-content">');

          $s2 .= sprintf('
          <a class="dropdown-item" target="_blank" href="print.php?mid=%s">Εκτύπωση</a>
          <a class="dropdown-item" target="_blank" href="print.php?mid=%s&pdf=1">PDF</a>',$lastmid,$lastmid);

        if ($lastmid > 0 && $a == 2 && $cnt == 0)
          $s2 .= sprintf('
          <a class="dropdown-item" target="_blank" href="print.php?clsid=%s&pdf=1">Εξωτερικό PDF</a>
           ',$r1['CLSID']);

           $s2 .= sprintf('
           </div>
           </div>
       </div> ');
*/
        if ($inc == 0)
        {
            if ($r1['TYPE'] == 2)  
                $s2 .= sprintf('<br><a target="_blank" href="print.php?mid=%s">Προβολή</a>',$lastmid);
            else
                $s2 .= sprintf('<br><a target="_blank" href="print.php?mid=%s">EKT</a>',$lastmid);
            if ($r1['TYPE'] == 0)  
            {
                $s2 .= sprintf(' &#x2022; <a target="_blank" href="print.php?mid=%s&pdf=1">PDF</a>',$lastmid);
                if ($lastmid > 0 && $a == 2 && $cnt == 0 && $r1['CLASSIFIED'] == 0)
                    $s2 .= sprintf(' &#x2022; <a target="_blank" href="print.php?clsid=%s&pdf=1">exPDF</a>',$r1['CLSID']);
            }
        }

        if ($lastmid > 0 && $a == 2 && $cnt == 0 && $inc == 0)
        {
/*            $s2 .= sprintf('&mdash; <div class="dropdown is-hoverable">
        <div class="dropdown-trigger">
          <a aria-haspopup="true" aria-controls="dropdown-menu">
            <span>Επεξεργασία</span>
          </a>
        </div>
        <div class="dropdown-menu"  role="menu">
          <div class="dropdown-content">');

              $s2 .= sprintf('
                <a class="dropdown-item" href="neweggr.php?did=%s&mid=%s">Επεξεργασία</a>
                <a class="dropdown-item" href="comments.php?did=%s&mid=%s">Σχόλια</a>
                <a class="dropdown-item" href="related.php?did=%s">Σχετικά</a>
                <a class="dropdown-item" href="recp.php?did=%s&mid=%s">Παραλήπτες</a>
                <a class="dropdown-item" href="att.php?did=%s&mid=%s">Επισυναπτόμενα</a>
                <a class="dropdown-item" href="sign.php?docs=%s">Ψηφιακή Υπογραφή</a>
                <a class="dropdown-item" href="javascript:delmid(%s);">Διαγραφή έκδοσης</a>
                 ',$r1['ID'],$lastmid,$r1['ID'],$lastmid,$r1['ID'],$r1['ID'],$lastmid,$r1['ID'],$lastmid,$r1['ID'],$lastmid);
                 */

            $s2 .= '<br>';
            $s2 .= sprintf(' <a href="neweggr.php?did=%s&mid=%s">Επεξεργασία</a> ',$r1['ID'],$lastmid);
            $s2 .= sprintf('&#x2022;  <a href="text.php?did=%s&mid=%s">Κείμενο</a> ',$r1['ID'],$lastmid);
            if ($showcomm)
               $s2 .= sprintf('&#x2022; <a href="comments.php?did=%s&mid=%s">Σχόλια</a>',$r1['ID'],$lastmid);
            if ($showsx)
                $s2 .= sprintf('&#x2022; <a href="related.php?did=%s&mid=%s">Σχετικά</a>',$r1['ID'],$lastmid);
            if ($showatt)
                $s2 .= sprintf('&#x2022; <a href="att.php?did=%s&mid=%s">Επισυναπτόμενα</a>',$r1['ID'],$lastmid);

            $cs = CanSign($r1['ID'],$u->uid);
            if ($cs == 0)
                {
                    $anyp = '';
                    $anypp = sprintf('shde_pwd_%s',$r1['ID']);
                    if (array_key_exists($anypp,$_SESSION))
                        $anyp = $_SESSION[$anypp];
                    
                    $s2 .= sprintf('&#x2022; <a href="sign.php?docs=%s&mid=%s">Ψηφιακή Υπογραφή</a> [%s] ',$r1['ID'],$lastmid,BuildSadesRequest($r1['ID']));

                }
            if ($a == 2)
                $s2 .= sprintf('&#x2022; <a href="javascript:delmid(%s);">Διαγραφή έκδοσης</a>',$lastmid);

        }
/*            $s2 .= sprintf('
                </div>
                </div>
            </div> ');
*/

        if ($r2['SIGNEDPDF'] && strlen($r2['SIGNEDPDF']) > 5)
            {
                if ($cnt == 0)
                    {
                        if ($inc == 0)
                            {
                                $s2 = sprintf('<br><a href="print.php?clsid=%s&pdf=1" target="_blank">Τελικό ΨΥ</a>',$r1['CLSID']);
                                if ($ur['CLASSIFIED'] == 0)
                                {
                                    $s2 .= sprintf('<br><a href="dvg.php?did=%s&mid=%s">Διαύγεια</a>',$r1['ID'],$r2['ID']);                        
                                }
                            }
                        if($r1['ADDEDSIGNERS'] && count(explode(",",$r1['ADDEDSIGNERS'])) >= 1 && $inc == 0 && $a == 2)
                        {
                            $s2 .= " [<br>".BuildSadesRequest($r1['ID']).']';
                        }
                    }
                else
                    $s2 = sprintf('<br><a href="print.php?did=%s&mid=%s&pdf=1" target="_blank"> ΨΥ</a>',$r1['ID'],$r2['ID']);
                $hasDS = 1;
            }

        if ($full)
        {
            $s .= sprintf($bu,date("d/m/Y H:i:s",$r2['DATE']),$r2['MSG'],$r2['INFO'],$s2);
        }
        else
        {
            $s .= sprintf("%s %s",date("d/m/Y H:i:s",$r2['DATE']),$s2);
        }
        $cnt++;
        $s .= '<br>';
        if (!$full)
            $s .= '<hr>';
    } 

    if ($inc == 0 && $a == 2)
    {
        if (!$full)
            $s .= sprintf('<a href="neweggr.php?did=%s&copy=%s">Νέα έκδοση</a><br>',$r1['ID'],$firstmid);
        else
            $s .= sprintf('<button class="button is-small is-danger autobutton" href="neweggr.php?did=%s&copy=%s">Νέα έκδοση</button><br>',$r1['ID'],$firstmid);
    }
    else
    {

    }
//    if ($inc)
        {
            $attc = CountDB("ATTACHMENTS WHERE MID = ?",array($lastmid));
            if ($attc)
                $s .= sprintf('<a href="att.php?did=%s&mid=%s">Επισυναπτόμενα (%s)</a><br>',$did,$lastmid,$attc);
        }
    if ($a == 2)
    {
        if ($inc || $sent)
        {

        }
        else
        {
            $s .= sprintf('<a href="javascript:delmid(%s);">Διαγραφή Έκδοσης</a><br>',$lastmid);
        }
    }
    $s .= sprintf('<a href="neweggr.php?forward=%s&forwardmid=%s">Προώθηση</a><br>',$did,$lastmid);
    if ($a == 2)
        $s .= sprintf('<a href="javascript:deldid(%s);">Διαγραφή</a>',$did);

    $s .= sprintf("</td>");

    $s .= sprintf("</tr>");
    return $s;
}

?>