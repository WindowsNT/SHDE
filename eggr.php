<?php

require_once "functions.php";
if (!$u)
    diez();

// QQ("DELETE FROM MESSAGES"); QQ("DELETE FROM DOCUMENTS");

if (array_key_exists("q",$_POST))
{
    redirect(sprintf("eggr.php?q=1&q_oids=%s&q_eids=%s&q_fids=%s&q_wids=%s&q_topic=%s&q_text=%s",
        implode(",", $_POST['q_oids']),
        implode(",",$_POST['q_eids']),
        implode(",",$_POST['q_fids']),
        implode(",",$_POST['q_wids']),
        $_POST['q_topic'],
        $_POST['q_text']
        ));
    die;
}


if (array_key_exists("function",$_POST))
{
    if ($_POST['function'] == "move")
    {
        if (UserAccessFolder($_POST['to'],$u->uid) != 2)
            die;
        $ret = array();
        QQ("BEGIN TRANSACTION;");
        foreach($_POST['items'] as $it)
        {
            if (UserAccessDocument($it,$u->uid) != 2)
                continue;
            
                $fr = FolderRow($_POST['to']);
                if ($fr)
                    QQ("UPDATE DOCUMENTS SET FID = ?,EID = ? WHERE ID = ?",array($_POST['to'],$fr['EID'],$it));
            $ret[$it] = DocTR($it,1);

        }
        QQ("COMMIT;");
        print(json_encode($ret));
        die;
    }
    if ($_POST['function'] == "readstate")
    {
        $ret = array();
        QQ("BEGIN TRANSACTION;");
        foreach($_POST['items'] as $it)
        {
            if (UserAccessDocument($it,$u->uid) != 2)
                continue;
            QQ("UPDATE DOCUMENTS SET READSTATE = ? WHERE ID = ?",array($_POST['state'],$it));
            $ret[$it] = DocTR($it,1,0,$_POST['oid'] == 0 ? 1 : 0,$_POST['eid'] == 0 ? 1 : 0,$_POST['fid'] == 0 ? 1 : 0);

        }
        QQ("COMMIT;");
        print(json_encode($ret));
        die;
    }
    print_r($_POST);
    die;
}
require_once "output.php";



if (!array_key_exists("fid",$req))
    $req['fid'] = 0;
if (!array_key_exists("eid",$req))
    $req['eid'] = 0;
if (!array_key_exists("oid",$req))
    $req['oid'] = 0;


$_SESSION['shde_eggrurl'] = $_SERVER['REQUEST_URI'];


if (!array_key_exists("shde_full",$_SESSION))
    $_SESSION['shde_full'] = 0;
if (array_key_exists("full",$req))
    $_SESSION['shde_full'] = $req['full'];
if (array_key_exists("toggle",$req))
{
    if ($req['toggle'] == 0)
    {
        foreach($_SESSION as $key => $arx)
        {
            if (is_string($key))
                if (substr($key,0,10) == "shde_hide_")
                    unset($_SESSION[$key]);
        }
        $_SESSION['shde_hide_comments'] = 1;
        $_SESSION['shde_hide_att'] = 1;
        $_SESSION['shde_hide_class'] = 1;
        $_SESSION['shde_hide_priority'] = 0;
        $_SESSION['shde_hide_sxetika'] = 1;
        $_SESSION['shde_full'] = 0;
    }
    if ($req['toggle'] == 1)
    {
        if (array_key_exists("shde_hide_topic",$_SESSION)) unset($_SESSION['shde_hide_topic']);
        else $_SESSION['shde_hide_topic'] = 1;
        
    }
    if ($req['toggle'] == 2)
    {
        if (array_key_exists("shde_hide_writer",$_SESSION)) unset($_SESSION['shde_hide_writer']);
        else $_SESSION['shde_hide_writer'] = 1;
    }
    if ($req['toggle'] == 3)
    {
        if (array_key_exists("shde_hide_folder",$_SESSION)) unset($_SESSION['shde_hide_folder']);
        else $_SESSION['shde_hide_folder'] = 1;
    }
    if ($req['toggle'] == 4)
    {
        if (array_key_exists("shde_hide_cat",$_SESSION)) unset($_SESSION['shde_hide_cat']);
        else $_SESSION['shde_hide_cat'] = 1;
    }
    if ($req['toggle'] == 5)
    {
        if (array_key_exists("shde_hide_for",$_SESSION)) unset($_SESSION['shde_hide_for']);
        else $_SESSION['shde_hide_for'] = 1;
    }
    if ($req['toggle'] == 6)
    {
        if (array_key_exists("shde_hide_ep",$_SESSION)) unset($_SESSION['shde_hide_ep']);
        else $_SESSION['shde_hide_ep'] = 1;
    }
    if ($req['toggle'] == 7)
    {
        if (array_key_exists("shde_hide_class",$_SESSION)) unset($_SESSION['shde_hide_class']);
        else $_SESSION['shde_hide_class'] = 1;
    }
    if ($req['toggle'] == 8)
    {
        if (array_key_exists("shde_hide_priority",$_SESSION)) unset($_SESSION['shde_hide_priority']);
        else $_SESSION['shde_hide_priority'] = 1;
    }
    if ($req['toggle'] == 9)
    {
        if (array_key_exists("shde_hide_sxetika",$_SESSION)) unset($_SESSION['shde_hide_sxetika']);
        else $_SESSION['shde_hide_sxetika'] = 1;
    }
    if ($req['toggle'] == 10)
    {
        if (array_key_exists("shde_hide_att",$_SESSION)) unset($_SESSION['shde_hide_att']);
        else $_SESSION['shde_hide_att'] = 1;
    }
    if ($req['toggle'] == 11)
    {
        if (array_key_exists("shde_hide_comments",$_SESSION)) unset($_SESSION['shde_hide_comments']);
        else $_SESSION['shde_hide_comments'] = 1;
    }

    $xoid = 0;$xeid = 0;$xfid = 0;
    if (array_key_exists("oid",$req)) $xoid = $req['oid'];
    if (array_key_exists("eid",$req)) $xeid = $req['eid'];
    if (array_key_exists("fid",$req)) $xfid = $req['fid'];

    redirect(sprintf("eggr.php?oid=%s&eid=%s&fid=%s",$xoid,$xeid,$xfid));
    die;
}

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


    $xoid = 0;$xeid = 0;$xfid = 0;
    if (array_key_exists("oid",$req)) $xoid = $req['oid'];
    if (array_key_exists("eid",$req)) $xeid = $req['eid'];
    if (array_key_exists("fid",$req)) $xfid = $req['fid'];
    $prov = sprintf(' &nbsp;<div class="dropdown is-hoverable ">
    <div class="dropdown-trigger block">
      <button class="button is-danger" aria-haspopup="true" aria-controls="dropdown-menu13">
        <span>Προβολή</span>
      </button>
    </div>
    <div class="dropdown-menu" id="dropdown-menu13" role="menu">
              <div class="dropdown-content">    
                  <a class="dropdown-item" href="eggr.php?full=0">Κανονική</a>
                  <a class="dropdown-item" href="eggr.php?full=1">Πλήρης</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="eggr.php?toggle=0">Επαναφορά Προεπιλογών</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="eggr.php?toggle=1&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=2&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=3&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=4&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=5&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=6&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=7&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=8&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=9&oid=%s&eid=%s&fid=%s">%s</a>
<!--                  <a class="dropdown-item" href="eggr.php?toggle=10&oid=%s&eid=%s&fid=%s">%s</a>
                  <a class="dropdown-item" href="eggr.php?toggle=11&oid=%s&eid=%s&fid=%s">%s</a>-->
              </div>
              </div>
            </div> ',
            $xoid,$xeid,$xfid,$showtopic == 1 ? '<b>Θέμα</b>' : 'Θέμα',
            $xoid,$xeid,$xfid,$showwriter == 1 ? '<b>Από</b>' : 'Από',
            $xoid,$xeid,$xfid,$showfolder == 1 ? '<b>Φάκελος</b>' : 'Φάκελος',
            $xoid,$xeid,$xfid,$showcat == 1 ? '<b>Κατηγορία</b>' : 'Κατηγορία',
            $xoid,$xeid,$xfid,$showfor == 1 ? '<b>Φορέας</b>' : 'Φορέας',
            $xoid,$xeid,$xfid,$showep == 1 ? '<b>Endpoint</b>' : 'Endpoint',
            $xoid,$xeid,$xfid,$showclass == 1 ? '<b>Διαβάθμιση</b>' : 'Διαβάθμιση',
            $xoid,$xeid,$xfid,$showpri == 1 ? '<b>Προτεραιότητα</b>' : 'Προτεραιότητα',
            $xoid,$xeid,$xfid,$showsx == 1 ? '<b>Σχετικά</b>' : 'Σχετικά',
            $xoid,$xeid,$xfid,$showatt == 1 ? '<b>Επισυναπτόμενα</b>' : 'Επισυναπτόμενα',
            $xoid,$xeid,$xfid,$showcomm == 1 ? '<b>Σχόλια</b>' : 'Σχόλια'
        );

    $buttons = ' &nbsp; <button href="shdeincoming.php" class="button autobutton is-success block">Λήψη</button> &nbsp; <button href="send.php" class="button autobutton is-link block">Αποστολή</button> &nbsp; <button href="neweggr.php" class="button autobutton is-primary  block">Νέο Έγγραφο</button> &nbsp;';

    if (!array_key_exists("q",$req))
    $src = sprintf(' &nbsp; <button href="search.php?q_oids=%s&q_eids=%s&q_fids=%s" class="block button autobutton is-warning block">Αναζήτηση</button> ',$req['oid'],$req['eid'],$req['fid']);
else
$src = sprintf(' &nbsp; <button href="search.php?q_oids=%s&q_eids=%s&q_fids=%s&q_topic=%s&q_text=%s" class="block button autobutton is-warning block">Προχωρημένη Αναζήτηση</button> ',$req['oid'],$req['eid'],$req['fid'],$req['q_topic'],$req['q_text']);

        $buttons .= $src;
        $buttons .= $prov;
        PrintHeader('index.php',$buttons);

//    echo $prov;
          
  
  

/*if ($_SESSION['shde_full'] == 1)
    printf(' <button href="eggr.php?full=0&eid=%s&fid=%s&oid=%s" class="block button autobutton is-success">Πλήρης προβολή</button> ',$req['eid'],$req['fid'],$req['oid']);
else
    printf(' <button href="eggr.php?full=1&eid=%s&fid=%s&oid=%s" class="block button autobutton is-success">Κανονική προβολή</button> ',$req['eid'],$req['fid'],$req['oid']);
    */
    







function Search($root,$nest)
{
    $fis = '';
    global $u,$req;
    if ($nest == 0)
        $q = QQ("SELECT * FROM FOLDERS WHERE SPECIALID IS NOT 0 ORDER BY EID ASC");
    else
        $q = QQ("SELECT * FROM FOLDERS WHERE SPECIALID = 0 AND PARENT = ?",array($root));
    $lasteid = 0;
    while($r = $q->fetchArray())
    {
        $a = UserAccessFolder($r['ID'],$u->uid);
        if ($a == 0)
            continue;
        $eid = $r['EID'];


        $fid = $r['ID'];

        if (UserAccessEP($eid,$u->uid) == 0)
            continue;
        $ex = '';
        if ($req['fid'] == $fid)
            {
                if ($r['SPECIALID'] == FOLDER_INBOX)
                    $ex = 'is-primary';
                else
                if ($r['SPECIALID'] == FOLDER_OUTBOX)
                    $ex = 'is-success';
                else
                if ($r['SPECIALID'] == FOLDER_SENT)
                    $ex = 'is-info';
                else
                if ($r['SPECIALID'] == FOLDER_TRASH)
                    $ex = 'is-danger';
                else
                    $ex = 'is-warning';
            }
        
        if ($nest == 0)
        {
            if ($lasteid != $eid)
            {
                $lasteid = $eid;
                $epr = EPRow($eid);
                if ($epr)
                    $fis .= sprintf('<br>%s<hr>',$epr['NAME']);
                if ($req['fid'] == 0 && $nest == 0 && $req['eid'] == $eid)
                    $fis .= sprintf('<button class="button autobutton  is-small is-warning" href="eggr.php?fid=0&eid=%s">Όλα</button>',$eid);
                else
                    $fis .= sprintf('<button class="button autobutton  is-small" href="eggr.php?fid=0&eid=%s">Όλα</button>',$eid);
            }
        }
        
        $fis .= sprintf('<button class="button autobutton  is-small %s" href="eggr.php?fid=%s">',$ex,$r['ID']);
        $nes = $nest;
        while($nes)
        {
            $nes--;
            $fis .= ' - ';
        }
        $fis .= sprintf('%s</button>',$r['NAME']);
        $nes = $nest;
        $fis .= Search($r['ID'],$nest + 1);
        
        if ($nest == 0)
            $fis .= '<br>';
    }
    return $fis;
}


function Selects()
{
    global $where;
    global $req;
    $s = sprintf(' 
    
    <script>
    function delmid(id)
    {
        if (confirm("Σίγουρα;"))
            window.location = "neweggr.php?deletemid=" + id;
    }
    function deldid(id)
    {
        if (confirm("Σίγουρα;"))
            window.location = "neweggr.php?deletedid=" + id;
    }

    function rld(result)
    {
        const obj = JSON.parse(result);
        const keys = Object.keys(obj);
        for (let i = 0; i < keys.length; i++) 
        {
            const key = keys[i];
            const val = obj[key];
            var ee = $("#doc" + key);
            ee.fadeOut("slow", function(){
                ee.replaceWith(val);
                ee.fadeIn("slow");
            });

        }
    }

    function ds()
    {
            var ids = new Array();
            $.each($(".checking"), function(i, val) {
                if ($(val).prop("checked") == false)
                    return;            
                var id = $(val).prop("id").substring(5);
                if (id == 0)
                    return;
    
                ids.push(id);
            });
            if (ids.length == 0)
                return;

            var str = String(ids);
            window.location = "sign.php?docs=" + str;
    
    }

    function rds()
    {
        if (!confirm("Σίγουρα;"))
            return;

            var ids = new Array();
            $.each($(".checking"), function(i, val) {
                if ($(val).prop("checked") == false)
                    return;            
                var id = $(val).prop("id").substring(5);
                if (id == 0)
                    return;
    
                ids.push(id);
            });
            if (ids.length == 0)
                return;

            var str = String(ids);
            window.location = "unsign.php?docs=" + str;
    
    }

    function unprot()
    {
        if (!confirm("Σίγουρα;"))
            return;

            var ids = new Array();
            $.each($(".checking"), function(i, val) {
                if ($(val).prop("checked") == false)
                    return;            
                var id = $(val).prop("id").substring(5);
                if (id == 0)
                    return;
    
                ids.push(id);
            });
            if (ids.length == 0)
                return;

            var str = String(ids);
            window.location = "unprot.php?docs=" + str;
    
    }

    function fulldelete()
    {
        if (!confirm("Σίγουρα;"))
            return;

            var ids = new Array();
            $.each($(".checking"), function(i, val) {
                if ($(val).prop("checked") == false)
                    return;            
                var id = $(val).prop("id").substring(5);
                if (id == 0)
                    return;
    
                ids.push(id);
            });
            if (ids.length == 0)
                return;

            var str = String(ids);
            window.location = "fulldelete.php?docs=" + str;
    
    }


    function checksent()
    {
            var ids = new Array();
            $.each($(".checking"), function(i, val) {
                if ($(val).prop("checked") == false)
                    return;            
                var id = $(val).prop("id").substring(5);
                if (id == 0)
                    return;
    
                ids.push(id);
            });
            if (ids.length == 0)
                return;

            var str = String(ids);
            window.location = "checksent.php?docs=" + str;
    
    }

    function prot(oid,eid,fid,did = 0)
    {
        if (!confirm("Σίγουρα;"))
            return;

            var ids = new Array();
            if (did == 0)
            {
                $.each($(".checking"), function(i, val) {
                    if ($(val).prop("checked") == false)
                        return;            
                    var id = $(val).prop("id").substring(5);
                    if (id == 0)
                        return;
        
                    ids.push(id);
                });
                if (ids.length == 0)
                    return;
            }
            else
            {
                ids.push(did);
            }
    
                $.ajax({
                    url: "protocol.php",
                    method: "POST",
                    data: {"items" : ids},
                    success: function (result) {

                        window.location = "eggr.php?oid=" + oid + "&eid=" + eid + "&fid=" + fid;
                    }
                });
    
    }
    function moveall(newfid)
    {
        var ids = new Array();
        $.each($(".checking"), function(i, val) {
            if ($(val).prop("checked") == false)
                return;            
            var id = $(val).prop("id").substring(5);
            if (id == 0)
                return;

            ids.push(id);
        });
        if (ids.length == 0)
            return;

            $.ajax({
                url: "eggr.php",
                method: "POST",
                data: {"function": "move", "to" : newfid, "items" : ids},
                success: function (result) {

                    rld(result);
                }
            });
        }

        function readstate(oid,eid,fid,state)
        {
            var ids = new Array();
            $.each($(".checking"), function(i, val) {
                if ($(val).prop("checked") == false)
                    return;            
                var id = $(val).prop("id").substring(5);
                if (id == 0)
                    return;
    
                ids.push(id);
            });
            if (ids.length == 0)
                return;
    
                $.ajax({
                    url: "eggr.php",
                    method: "POST",
                    data: {"function": "readstate", "state" : state, "items" : ids, "oid" : oid,"eid" : eid,"fid" : fid},
                    success: function (result) {    
                        rld(result);
                    }
                });
            }
    
    function sall()
    {
        $(".checking").prop( "checked", true );
    }
    function snone()
    {
        $(".checking").prop( "checked", false );
    }
    </script>
    %s
    Επιλογή: <button class="button is-small is-link is-rounded" onclick="javascript:sall();">όλα</button> &mdash; <button class="button is-small is-danger is-rounded" onclick="javascript:snone();">κανένα</button> και με τα 
    Επιλεγμένα: ',$where);

    $s .= sprintf('
    <script>
    function incmail()
    {
            window.location = "incomingmail.php?manual=1&oid=%s&eid=%s&fid=%s";
    }

    </script>',$req['oid'],$req['eid'],$req['fid']);

    return $s;
}

function SearchFilter($did)
{
    global $req;
    global $u;
    if (!array_key_exists("q",$req))
        return false;


    $dr = DRow($did,1);
    if (!$dr)
        return false;

    $ur = UserRow($u->uid);

    $eid = $dr['EID'];
    $wid = $dr['UID'];
    $eidr = EPRow($eid);

    $oid = $eidr['OID'];
    $oidr = FRow($oid);

    $fid = $dr['FID'];
    $fr = FolderRow($fid);

    // By oid
    $oids = explode(",",$req['q_oids']);
    if (count($oids))
    {
        if (!in_array(0,$oids))
        {
            if (!in_array($oid,$oids))
                return true; 
        }
    }

    // By eid
    $eids = explode(",",$req['q_eids']);
    if (count($eids))
    {
        if (!in_array(0,$eids))
        {
            if (!in_array($eid,$eids))
                return true; 
        }
    }

    
    // By fid
    $fids = explode(",",$req['q_fids']);
    if (count($fids))
    {
        if (!in_array(0,$fids))
        {
            if (!in_array($fid,$fids))
                return true; 
        }
    }

        
    // By wid
    $wids = explode(",",$req['q_wids']);
    if (count($wids))
    {
        if (!in_array(0,$wids))
        {
            if (!in_array($wid,$wids))
                return true; 
        }
    }

    // By topic
    if (strlen($req['q_topic']))
    {
        if (mb_stripos($dr['TOPIC'],$req['q_topic']) === FALSE)
            return true;
    }

    // By text
    if (strlen($req['q_text']))
    {
        $q6 = QQ("SELECT * FROM MESSAGES WHERE DID = ?",array($dr['ID']));
        while($r6 = $q6->fetchArray())
        {
            if (mb_stripos($r6['MSG'],$req['q_text']) === FALSE)
                return true;
        }
    }



    return 0;
}

function PrintMyDocuments($uid,$fid = 0,$eid = 0,$oid = 0,$full = 0,$did = 0)
{
    global $req;
    if (array_key_exists("q",$req))
        {
            $fid = 0;
            $eid = 0;
            $oid = 0;
            $did = 0;
        }
    $topfoldertype = 0;
    if ($fid)
    {
        $f1 = FolderRow($fid);
        $topfoldertype = TopFolderType($fid);
        $eid = $f1['EID'];
    }
    if ($eid)
    {
        $f2 = EPRow($eid);
        $oid = $f2['OID'];
    }
    $oidr = FRow($oid);
    $eidr = EPRow($eid);
    $ur = UserRow($uid);

    $where = '';
    if ($oid)
        $where .= sprintf('<b><a href="eggr.php?oid=%s" style="color: red">%s</a></b> ',$oidr['ID'],$oidr['NAME']);
    if ($eid)
        $where .= sprintf('&mdash; <b><a href="eggr.php?oid=%s&eid=%s" style="color: blue !important">%s</a></b> ',$oidr['ID'],$eidr['ID'],$eidr['NAME']);
    if ($fid)
        {
            if ($oidr && $eidr)
                $where .= sprintf('&mdash; <b><a href="eggr.php?oid=%s&eid=%s&fid=%s" style="color: green !important">%s</a></b> ',$oidr['ID'],$eidr['ID'],$f1['ID'],$f1['NAME']);
            else
                $where .= sprintf('&mdash; <b><a href="eggr.php?fid=%s" style="color: green !important">%s</a></b> ',$f1['ID'],$f1['NAME']);
        }
    $where .= '<br>';

    $s = Selects();


    $s .= '<div class="dropdown is-hoverable">
    <div class="dropdown-trigger">
      <button class="button  is-info is-small" aria-haspopup="true" aria-controls="dropdown-menu14">
        <span>Μετακίνηση</span>
      </button>
    </div>
    <div class="dropdown-menu" id="dropdown-menu14" role="menu">
      <div class="dropdown-content">';

      if ($did)
        $s = '';
      else
      {
        $q6 = QQ("SELECT * FROM FOLDERS WHERE (LID = 0 OR LID IS NULL) ORDER BY EID ASC, NAME ASC");
        
        $laster = null;
        while($r6 = $q6->FetchArray())
        {
            if (UserAccessFolder($r6['ID'],$uid) == 0)
                continue;

            if (TopFolderType($r6['ID']) == FOLDER_SENT)
                continue;

            if ($laster == null || $laster['ID'] != $r6['EID'])
            {
                if ($laster)
                    $s .= '<div class="dropdown-divider"></div>';
                $laster = EPRow($r6['EID']);
                $s .= sprintf('      
                <b><center>&nbsp; %s &nbsp; </center></b><a class="dropdown-item" href="javascript:moveall(%s);">%s <font color="red">%s</font></a>
            ',$laster ? $laster['NAME'] : '',$r6['ID'],$r6['NAME'],ClassificationString2($r6['CLASSIFIED']));

            }
            else
                $s .= sprintf('     
                <a class="dropdown-item" href="javascript:moveall(%s);">%s <font color="red">%s</font></a>
            ',$r6['ID'],$r6['NAME'],ClassificationString2($r6['CLASSIFIED']));
        }
    }

      $s .= '
      </div>
    </div>
  </div> ';


  $s .= sprintf('<div class="dropdown is-hoverable">
  <div class="dropdown-trigger">
    <button class="button  is-primary is-small" aria-haspopup="true" aria-controls="dropdown-menu13">
      <span>Λειτουργίες</span>
    </button>
  </div>
  <div class="dropdown-menu" id="dropdown-menu13" role="menu">
    <div class="dropdown-content">    
                <a class="dropdown-item" href="javascript:prot(%s,%s,%s);">Πάρε πρωτόκολλο</a>
                <a class="dropdown-item" href="javascript:readstate(%s,%s,%s,1);">Σήμανση ως μη διαβασμένα</a>
                <a class="dropdown-item" href="javascript:readstate(%s,%s,%s,0);">Σήμανση ως διαβασμένα</a>
                <a class="dropdown-item" href="javascript:ds();">Ψηφιακή Υπογραφή</a>
                <a class="dropdown-item" href="javascript:unprot();">Aφαίρεση Πρωτοκόλλου</a>
                <a class="dropdown-item" href="javascript:rds();">Aφαίρεση Ψηφιακής Υπογραφής</a>
                <a class="dropdown-item" href="javascript:checksent();">Έλεγχος παράδοσης</a>
                <a class="dropdown-item" href="javascript:fulldelete();">Οριστική Διαγραφή</a>
            
            </div>
            </div>
          </div> ',$oid,$eid,$fid,$oid,$eid,$fid,$oid,$eid,$fid);
        
        
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
        
    $s .= sprintf('
    <hr>
    <table class="table datatable">
    <thead>
        <th class="all">ID</th>
        %s
%s
%s
        %s
       %s
       %s
        %s
        %s
        %s
        %s
        <th  class="all">Πρωτόκολλο</th>
        <th  class="all">Παραλήπτες</th>
        <th  class="all">Εκδόσεις</th>
    </thead>
    <tbody>

  ',
  $showtopic ? '<th  class="all">Θέμα</th>' : '',
  $showwriter ? '<th  class="all">Από</th>' : '',
  $fid == 0 && $showfolder ? '<th  class="all">Φάκελος</th>' : '',
  $showcat ? '<th  class="all">Κατηγορία</th>' : '',
  (($oid == 0  && $showfor) || $topfoldertype != FOLDER_OUTBOX) ? ' <th  class="all">Φορέας</th>' : '',
  (($eid == 0 && $showep) ||   $topfoldertype != FOLDER_OUTBOX) ? ' <th  class="all">Endpoint</th>' : '',
  $ur['CLASSIFIED'] > 0  && $showclass ? '<th  class="all">Διαβάθμιση</th>' : '',
  $showpri ? '<th  class="all">Προτεραιότητα</th>' : '',
  $showsx ? '<th  class="all">Σχετικά</th>' : '',
  $showcomm ? '<th  class="all">Σχόλια</th>' : '',


);
    $q1 = QQ("SELECT * FROM DOCUMENTS ORDER BY ID DESC");
    if ($did)
    {
        $s = '';
        $q1 = QQ("SELECT * FROM DOCUMENTS WHERE ID = ?",array($did));
    }
    while($r1 = $q1->fetchArray())
    {
        DocumentDecrypt($r1);
        if (SearchFilter($r1['ID']))
            continue;
        if (UserAccessEP($r1['EID'],$uid) == 0)
            continue;        
        $a = UserAccessDocument($r1['ID'],$uid);
        if ($a == 0)
            continue;
        $a4 = UserAccessFolder($r1['FID'],$uid);
        if ($a4 == 0)
          continue;

        if ($oid != 0)
        {
            $er = EPRow($r1['EID']);
            if (!$er)
                continue;
            if ($er['OID'] != $oid)
                continue;
        }
    
        if ($fid != 0 && $r1['FID'] != $fid)
            continue;
        if ($eid != 0 && $r1['EID'] != $eid)
            continue;

        $s .= DocTR($r1,$a,$full,$oid == 0 || $topfoldertype != FOLDER_OUTBOX,$eid == 0  || $topfoldertype != FOLDER_OUTBOX,$fid == 0);
    }
    if ($did == 0)
    {
        $s .= '  </tbody>
        </table>
    ';
    }
    return $s;
}

//$left = Search(0,0);
$oids = OidsThatCanAccess($u->uid);
if (array_key_exists("notif",$_SESSION))
{
    printf('<article class="message is-primary">
    <div class="message-header">
      <p>Ειδοποίηση</p>
    </div>
    <div class="message-body">
    %s
    </div>
  </article>',$_SESSION['notif']);
  unset($_SESSION['notif']);
}

if (count($oids) == 1)
    $left = Tree($u->uid,1,$oids[0],null,null,0,array($req['oid'],$req['eid'],$req['fid']));
else
    $left = Tree($u->uid,1,null,null,null,0,array($req['oid'],$req['eid'],$req['fid']));
$right = PrintMyDocuments($u->uid,$req['fid'],$req['eid'],$req['oid'],$_SESSION['shde_full']);

if ($mobile)
{
    echo $left;
    echo $right;
}
else
{
?>

<div class="columns">
  <div class="column is-2">
    <?= $left ?>
  </div>
  <div class="column is-10">
    <?= $right ?>
  </div>
</div>
<?php
}

?>

