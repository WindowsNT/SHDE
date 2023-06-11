<?php

/*

    Diavgeia

*/

ini_set('display_errors', 1); error_reporting(E_ALL);
$timezone = "Europe/Athens";
date_default_timezone_set($timezone);
$mobile = 0;
$useragent=$_SERVER['HTTP_USER_AGENT'];

if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
    $mobile = 1;
$iPod    = stripos($useragent,"iPod");
$iPhone  = stripos($useragent,"iPhone");
$iPad    = stripos($useragent,"iPad");
if ($iPod || $iPhone || $iPad)
    $mobile = 1;
require_once "configuration.php";

// Default Formatting for Options in Documents
$defform = serialize(array("form_recp" => "0"));


// Role IDs
define('ROLE_USER', 24);
define('ROLE_EDITOR', 23);
define('ROLE_EPADMIN', 22);
define('ROLE_SIGNER0', 21);
define('ROLE_FADMIN', 20);

define('FOLDER_INBOX',1);
define('FOLDER_OUTBOX',2);
define('FOLDER_SENT',3);
define('FOLDER_TRASH',4);
define('FOLDER_ARCHIVE',5);


function NestX($char,$nest)
{
    $f = '';
    while($nest > 0)
        {
            $nest--;
            $f .= $char;
        }
    return $f;
}

function ClassificationString($id,$print = 0)
{
    if ($id == 3) return "ΑΚΡΩΣ ΑΠΟΡΡΗΤΟ";
    if ($id == 2) return "ΑΠΟΡΡΗΤΟ";
    if ($id == 1) return "ΕΜΠΙΣΤΕΥΤΙΚΟ";
    if ($print == 1)
        return "";
    return "ΚΑΝΟΝΙΚΟ";
}


function PriorityString($id,$print = 0)
{
    if ($id == 2) return "ΕΞ. ΕΠΕΙΓΟΝ";
    if ($id == 1) return "ΕΠΕΙΓΟΝ";
    if ($print == 1)
        return "";
    return "ΑΠΛΟ";
}

function ClassificationString2($id)
{
    if ($id == 3) return "ΑΑ";
    if ($id == 2) return "Α";
    if ($id == 1) return "Ε";
    return "";
}

// -----------

$req = array_merge($_GET,$_POST);
session_start();
require_once "db.php";

class U
{
    public $uid = "";
    public $username = "";
    public $lastname = "";
    public $firstname = "";
    public $title = "";
    public $superadmin = 0;
    public $fadmin = array();
    public $epadmin = array();
    public $row = null;
};

$u = null;
if (array_key_exists("sl_attr",$_SESSION))
{
    if (array_key_exists("gsntaxnumber",$_SESSION['sl_attr']))
    {
        $_SESSION['shde_username'] = $_SESSION['sl_attr']['gsntaxnumber'];
        $_SESSION['shde_firstname'] = $_SESSION['sl_attr']['givenname'];
        $_SESSION['shde_lastname'] = $_SESSION['sl_attr']['sn'];
        $_SESSION['shde_title'] = $_SESSION['sl_attr']['title'];
    }
}

if (array_key_exists("shde_username",$_SESSION))
    {
        $u = new U;
        $u->username = $_SESSION['shde_username'];
        $u->firstname = $_SESSION['shde_firstname'];
        $u->lastname = $_SESSION['shde_lastname'];
        $u->title = $_SESSION['shde_title'];
        if ($u->username == $superadminuid)
            $u->superadmin = 1;

        // Save to db
        $r1 = QQ("SELECT * FROM USERS WHERE USERNAME = ?",array($u->username))->fetchArray();
        if (!$r1)
            {
                QQ("INSERT INTO USERS (USERNAME,LASTNAME,FIRSTNAME,TITLE) VALUES(?,?,?,?)",array($u->username,$u->lastname,$u->firstname,$u->title));
                $u->uid = $lastRowID;
            }
        else
            {
                $u->uid = $r1['ID'];
                $u->row = $r1;
                if (isset($_SESSION['shde_needbio']) && $_SESSION['shde_needbio'] == 2)
                    $u->row['CLASSIFIED'] = 0;
            }

       
        if ($r1 && $r1['CLASSIFIED'] > 0 && !isset($in_bio) && !isset($_SESSION['shde_bio']))
        {
            if (isset($_SESSION['shde_needbio']) && $_SESSION['shde_needbio'] == 2)
            {
              }
            else
            {
                $_SESSION['shde_needbio'] = 1;
                redirect("bio.php");
                die;
            }
        }


        $q1 = QQ("SELECT * FROM ROLES WHERE UID = ? AND ROLEID = ?",array($u->uid,ROLE_EPADMIN));
        while ($r1 = $q1->fetchArray())
            $u->epadmin[] = $r1['EID'];            

        $q1 = QQ("SELECT * FROM ROLES WHERE UID = ? AND ROLEID = ?",array($u->uid,ROLE_FADMIN));
        while ($r1 = $q1->fetchArray())
            $u->fadmin[] = $r1['OID'];            
        }

// -----------------

function PrintHeader($back = '',$front = '')
{
    global $u;
    global $mobile;
    global $login_taxis,$login_demo,$login_bio,$login_psd;
    ?>
    <div class="content" style="margin:20px;">
      <?php
      if ($u)
        {
            if (strlen($back) || strlen($front))
                $back = sprintf('<button href="%s" class="autobutton button is-danger block">Πίσω</button> %s<span></span>',$back,$front);
            $cols = '<nav class="level">
            <div class="level-left">
            <div class="level-item">
            %s
            </div></div>
            <div class="level-right">
            <div class="level-item">
            %s
            </div>
            </div>
          </div>
          <div class="content" style="margin:20px">';
            $logoutx = sprintf('<button href="logout.php?return=index.php" class="button  is-danger autobutton block"><img src="icon.svg" width="20" height="20" /> &nbsp; Logout &mdash;&nbsp; <b>%s %s</b></button><span></span>',$u->lastname,$u->firstname);
            if ($mobile)
            printf('%s %s<div class="content" style="margin:20px">',$back,$logoutx);
            else
            printf($cols,$back,$logoutx);
        }
        else
        {
            $s2 = sprintf('<img src="icon.svg" width="40" height="40" />  <div class="dropdown is-hoverable">
            <div class="dropdown-trigger">
              <button class="button is-link" aria-haspopup="true" aria-controls="dropdown-menu46">
                <span>Login</span>
              </button>
            </div>      

            <div class="dropdown-menu" id="dropdown-menu46" role="menu">
              <div class="dropdown-content">');

              if (strlen($login_taxis))
                  $s2 .='
                    <a href="login.php?type=taxis&return=index.php" class="dropdown-item">Taxis</a>';

              if (strlen($login_psd))
                  $s2 .='
                    <a href="login.php?type=psd&return=index.php" class="dropdown-item">ΠΣΔ</a>';

                if ($login_bio)
                    $s2 .='
                      <a href="login.php?type=bio&return=index.php" class="dropdown-item">Biometric</a>';
  
            if ($login_demo)
                  $s2 .='
                    <a href="login.php?type=demo&return=index.php" class="dropdown-item">Demo</a>';

                $s2 .= '
              </div>
            </div>
          </div> 
          
          <img src="ed.png" width="40" height="40" /> <a href="https://www.digitalawards.gov.gr" target="_blank"><img src="award.png" width="40" height="40" title="Βραβείο Υπουργείου Ψηφιακής Διακυβέρνησης"/></a>  <button class="button is-secondary" >
          MSA-APPS.COM SHDE
    </button>
';

           echo $s2;
            //echo '<button href="login.php?return=index.php" class="button is-primary autobutton">Login</button>';
        }
      ?>
<hr>
    <?php
}

function diez()
{
    if (!headers_sent())
        header("Location: index.php");
    else 
        printf("Έχετε αποσυνδεθεί, κάντε ξανά είσοδο <a href=\"index.php\">εδώ</a>.");
    die;
    
}

function redirect($filename,$u = 0) {
    if (!headers_sent() && $u == 0)
        header('Location: '.$filename);
    else {
        if ($u == 0)
        {
            echo '<script type="text/javascript">';
            echo 'window.location.href="'.$filename.'";';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="'.$u.';url='.$filename.'" />';
            echo '</noscript>';
        }
        else
            echo '<meta http-equiv="refresh" content="'.$u.';url='.$filename.'" />';
    }
}


function printr($v)
{
    printf("<xmp>");
    print_r($v);
    printf("</xmp>");
}


function printdie($v)
{
    printr($v);
    die;
}




function UserRow($id)
{
    global $u;
    if ($u && $u->uid == $id)
        return $u->row;
    return QQ("SELECT * FROM USERS WHERE ID = ?",array($id))->fetchArray();
}
function FRow($id)
{
    return QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($id))->fetchArray();
}

function FolderRow($id)
{
    return QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($id))->fetchArray();
}
function LockerRow($id)
{
    return QQ("SELECT * FROM LOCKERS WHERE ID = ?",array($id))->fetchArray();
}
function EPRow($id)
{
    return QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($id))->fetchArray();
}


function OrgChartFullName($id,$nest = 0,$deep = 1,$isid = 0)
{
    $n = '';
    if ($isid)
        $y1 = QQ("SELECT * FROM ORGCHART WHERE ID = ?",array($id))->fetchArray();
    else
        $y1 = QQ("SELECT * FROM ORGCHART WHERE CODE = ?",array($id))->fetchArray();
    if (!$y1)
        return $n;
    $sddd = $y1['SDDD'];
    if ($nest)
        $n .= '&mdash;';
    $n .= $y1['NAME'];
    if ($y1['PARENT'] && $deep == 1)
        $n .= OrgChartFullName($y1['PARENT'],$nest + 1,$deep,1);
    if ($y1['PARENT'] && $deep == 2 && $sddd == 0)
        $n .= OrgChartFullName($y1['PARENT'],$nest + 1,$deep,1);

    if ($nest == 0)
        return implode("&mdash;",array_reverse(explode("&mdash;",$n)));
    return $n;
}

function ReceipientArrayText($did)
{

    // Receipients 
    $doc = Drow($did,1);
    if (!$doc)
        return array();
    $inc = IsIncoming($doc);
    $rr = array();
    if ($doc['RECPX'] && strlen($doc['RECPX']))
    {
        foreach(unserialize($doc['RECPX']) as $y)
        {
            $ar = OrgChartFullName($y,0,2);
            if ($inc)
                $rr[] = sprintf('<a href="neweggr.php?reply=%s&ks=%s" title="Απάντηση εδώ">%s</a>',$did,$y,$ar);
            else
                $rr[] = sprintf('%s',$ar);
        }
    }
    if ($doc['RECPY'] && strlen($doc['RECPY']))
    {
        foreach(unserialize($doc['RECPY']) as $y)
        {
            $y1 = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($y))->fetchArray();
            if ($y1)
                $rr[] = sprintf("%s %s",$y1['LASTNAME'],$y1['FIRSTNAME']);
        }
    }
    if ($doc['RECPZ'] && strlen($doc['RECPZ']))
    {
        $ar = explode(",",$doc['RECPZ']);
        foreach($ar as $y)
        {
            if ($inc)
                $rr[] = sprintf('<a href="neweggr.php?reply=%s&ml=%s"  title="Απάντηση εδώ">%s</a>',$did,$y,$y);
            else
                $rr[] = $y;
        }
    }
    return $rr;
}

function FromArrayText($did)
{
    // Receipients 
    $doc = Drow($did,1);
    if (!$doc)
        return array();
    $rr = array();
    if ($doc['FROMX'] && strlen($doc['FROMX']))
    {
        foreach(unserialize($doc['FROMX']) as $y)
        {
            $ar = OrgChartFullName($y,0,2);
            $rr[] = sprintf('<a href="neweggr.php?reply=%s&ks=%s"  title="Απάντηση εδώ">%s</a>',$did,$y,$ar);
        }
    }
    if ($doc['FROMY'] && strlen($doc['FROMY']))
    {
        foreach(unserialize($doc['FROMY']) as $y)
        {
            $y1 = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($y))->fetchArray();
            if ($y1)
                $rr[] = sprintf("%s %s",$y1['LASTNAME'],$y1['FIRSTNAME']);
        }
    }
    if ($doc['FROMZ'] && strlen($doc['FROMZ']))
    {
        $ar = explode(",",$doc['FROMZ']);
        foreach($ar as $y)
        {
            $rr[] = sprintf('<a href="neweggr.php?reply=%s&ml=%s"  title="Απάντηση εδώ">%s</a>',$did,$y,$y);
        }
    }
    return $rr;
}

function KoinArrayText($did)
{
    // Receipients 
    $doc = Drow($did,1);
    if (!$doc)
        return array();
    $inc = IsIncoming($doc);
    $rr = array();
    if ($doc['KOINX'] && strlen($doc['KOINX']))
    {
        foreach(unserialize($doc['KOINX']) as $y)
        {
            $ar = OrgChartFullName($y,0,2);
            if ($inc)
                $rr[] = sprintf('<a href="neweggr.php?reply=%s&ks=%s">%s</a>',$did,$y,$ar);
            else
                $rr[] = sprintf('%s',$ar);
        }
    }
    if ($doc['KOINY'] && strlen($doc['KOINY']))
    {
        foreach(unserialize($doc['KOINY']) as $y)
        {
            $y1 = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($y))->fetchArray();
            if ($y1)
                $rr[] = sprintf("%s %s",$y1['LASTNAME'],$y1['FIRSTNAME']);
        }
    }
    if ($doc['KOINZ'] && strlen($doc['KOINZ']))
    {
        $ar = explode(",",$doc['KOINZ']);
        foreach($ar as $y)
        {
            if ($inc)
                $rr[] = sprintf('<a href="neweggr.php?reply=%s&ml=%s">%s</a>',$did,$y,$y);
            else
                $rr[] = $y;
        }
    }
    return $rr;
}


function BCCArrayText($did)
{
    // Receipients 
    $doc = Drow($did,1);
    if (!$doc)
        return array();
    $inc = IsIncoming($doc);
    $rr = array();
    if ($doc['BCCX'] && strlen($doc['BCCX']))
    {
        foreach(unserialize($doc['BCCX']) as $y)
        {
            $ar = OrgChartFullName($y,0,2);
            if ($inc)
                $rr[] = sprintf('<a href="neweggr.php?reply=%s&ks=%s">%s</a>',$did,$y,$ar);
            else
                $rr[] = $ar;
        }
    }
    if ($doc['BCCY'] && strlen($doc['BCCY']))
    {
        foreach(unserialize($doc['BCCY']) as $y)
        {
            $y1 = QQ("SELECT * FROM ADDRESSBOOK WHERE ID = ?",array($y))->fetchArray();
            if ($y1)
                $rr[] = sprintf("%s %s",$y1['LASTNAME'],$y1['FIRSTNAME']);
        }
    }
    if ($doc['BCCZ'] && strlen($doc['BCCZ']))
    {
        $ar = explode(",",$doc['BCCZ']);
        foreach($ar as $y)
        {
            $rr[] = $y;
        }
    }
    return $rr;
}

function EswArrayText($did)
{
    // Receipients 
    $doc = Drow($did,1);
    if (!$doc)
        return array();
    $inc = IsIncoming($doc);
    $rr = array();
    if ($doc['ESWX'] && strlen($doc['ESWX']))
    {
        foreach(unserialize($doc['ESWX']) as $y)
        {
            $ar = OrgChartFullName($y,0,2);
            if ($inc)
                $rr[] = sprintf('<a href="neweggr.php?reply=%s&ks=%s">%s</a>',$did,$y,$ar);
            else
                $rr[] = $ar;
        }
    }
    return $rr;
}



function PickFolder($eid,$name = "parent",$sel = array(0),$uid = 0,$m = 0,$oid = 0,$andall = 1,$lid = 0)
{
    $s = sprintf('<select  name="%s" class="input chosen-select" %s>',$name,$m == 1 ? "multiple" : "");
    if ($andall == 2)   
        $s .= sprintf('<option value="0" %s>(Όλοι)</option>',in_array(0,$sel) ? "selected" : 0);
    else
        $s .= sprintf('<option value="0" %s>(Κανένα)</option>',in_array(0,$sel) ? "selected" : 0);
    if ($eid == 0 && $lid == 0)
        $q2 = QQ("SELECT * FROM FOLDERS ORDER BY NAME ASC");
    else
    if ($lid )
        $q2 = QQ("SELECT * FROM FOLDERS WHERE LID = ? ORDER BY NAME ASC",array($lid));
    else
        $q2 = QQ("SELECT * FROM FOLDERS WHERE EID = ? ORDER BY NAME ASC",array($eid));
    while($r2 = $q2->fetchArray())
    {
        if ($uid && UserAccessFolder($r2['ID'],$uid) == 0)
            continue;

        if ($eid)
        {
            $pare = EPRow($r2['EID']);
            if (!$pare)
                continue;
            if ($pare['OID'] != $oid && $oid != 0)
                continue;
            $or = FRow($pare['OID']);
            if (!$or)
                continue;
        }

        $n = $r2['NAME'];
        if ($lid)
        {
            
        }
        else
        {
            if ($eid == 0 || $oid == 0)
            {
                $n = sprintf("%s &mdash; %s &mdash; %s",$or['NAME'],$pare['NAME'],$r2['NAME']);
            }
        }

        $s .= sprintf('<option value="%s" %s>%s</option>',$r2['ID'],in_array($r2['ID'],$sel) ? "selected" : "", $n);
    }
  $s .= '      </select>';
  return $s;
}


function OrgChartFullName2($id)
{
    $q1 = QQ("SELECT * FROM ORGCHART WHERE ID = ?",array($id))->fetchArray();
    if (!$q1)
        return '';

    $n = array($q1['NAME']);
    if ($q1['PARENT'] > 0)
    {
        $x = OrgChartFullName2($q1['PARENT']);
        foreach($x as $xx)
            $n[] = $xx;
    }
    return $n;
}

function PickReceipientsKS($name = "intr[]",$sel = array())
{
    $s = sprintf('<select  name="%s" class="input chosen-select" multiple>',$name);
    $q1 = QQ("SELECT * FROM ORGCHART WHERE ACTIVE = 1 ORDER BY NAME ASC");
    while($r1 = $q1->fetchArray())
    {
        $s .= sprintf('<option value="%s" %s>%s</option>',$r1['ID'],in_array($r1['ID'],$sel) ? "selected" : "", $r1['FULLNAME']);
    }
    $s .= '      </select>';
    return $s;
  }

function PickReceipientsAB($oid,$eid,$uid,$did,$name = "ext[]",$sel = array(),$classification = 0)
{
    $s = sprintf('<select  name="%s" class="input chosen-select" multiple>',$name);
    $q2 = QQ("SELECT * FROM ADDRESSBOOK ORDER BY LASTNAME ASC");
    while($r2 = $q2->fetchArray())
    {
        if ($uid && UserAccessAB($r2['ID'],$uid) == 0)
            continue;

            if ($r2['CLASSIFIED'] < $classification)
                continue;

            if ($r2['OID'] != 0 && $r2['OID'] != $oid)
                continue;
            if ($r2['EID'] != 0 && $r2['EID'] != $eid)
                continue;

        $s .= sprintf('<option value="%s" %s>%s %s</option>',$r2['ID'],in_array($r2['ID'],$sel) ? "selected" : "", $r2['LASTNAME'],$r2['FIRSTNAME']);
    }
  $s .= '      </select>';
  return $s;
}

function PickClassification($name = "classification",$sel = array(),$pwd = "",$r1 = null)
{
    global $u;
    $ur = UserRow($u->uid);
    $s = sprintf('<select id="%s" name="%s" class="input chosen-select">',$name,$name);
    $start = 0;
    // if already classified, cannot go back
    foreach($sel as $sss)
    {
        if ($sss > 0)
            $start = 1;
    }
    for($i = $start ; $i < 4 ; $i++)
    {
        if ($ur['CLASSIFIED'] < $i && $u->superadmin == 0)
            break;
        $s .= sprintf('<option value="%s" %s>%s</option>',$i,in_array($i,$sel) ? "selected" : 0,ClassificationString($i));
    }
  $s .= '      </select>';
  if (strlen($pwd))
  {
    $anyp = '';
    if ($r1)
    {
        $anypp = sprintf('shde_pwd_%s',$r1['ID']);
        if (array_key_exists($anypp,$_SESSION))
            $anyp = $_SESSION[$anypp];
    }

    $xy = '';
    if (count($sel) > 0 && $sel[0] > 0)
        $xy = sprintf('$("#%s3").show();',$pwd);
    $s .= sprintf('<div id="%s3" style="display:none;"><br><br>Κωδικός εγγράφου:<br><input class="input" type="password" name="%s1" value="%s" autocomplete="one-time-code"/>Κωδικός ξανά:<br><input class="input" type="password" name="%s2" value="%s" autocomplete="one-time-code"/></div>',$pwd,$pwd,$anyp,$pwd,$anyp);
    $s .= sprintf('<script>$("#%s").on("change", function () {    
        var selectVal = $("#%s option:selected").val();
        if (selectVal == 0)
            $("#%s3").hide();
        else
            $("#%s3").show();
       });
       %s
       
       </script>',$name,$name,$pwd,$pwd,$xy);
  }
  return $s;
}


function PickPriority($name = "priority",$sel = array())
{
    $s = sprintf('<select id="%s" name="%s" class="input chosen-select">',$name,$name);
    $start = 0;
    for($i = 0 ; $i < 3 ; $i++)
    {
        $s .= sprintf('<option value="%s" %s>%s</option>',$i,in_array($i,$sel) ? "selected" : 0,PriorityString($i));
    }
  $s .= '      </select>';
  return $s;
}

function PickUser($name = "uid",$sel = array(),$m = 0,$foreis = array(),$andall = 0,$uid = 0,$eps = array())
{
    $s = sprintf('<select type="select" name="%s" class="input chosen-select" %s>',$name,$m  == 1 ? "multiple" : "");

    if ($andall == 1)
    {
        if (!in_array(0,$sel))   
            $s .= sprintf('<option value="0">(Όλα)</option>');
        else
            $s .= sprintf('<option value="0" selected>(Όλα)</option>');
    }

    $q = QQ("SELECT * FROM USERS");
    while($r = $q->fetchArray())
    {
        if (count($foreis) > 0)
        {
            $found = 0;
            foreach($foreis as $fid)
            {
                if ($fid == 0) 
                {
                    $found = 1;
                    break;
                }
                $q9 = QQ("SELECT * FROM ROLES WHERE OID = ? AND UID = ?",array($fid,$r['ID']))->fetchArray();
                if ($q9 && ($uid == 0 || UserAccessOID($fid,$uid)))
                {
                    $found = 1;
                    break;
                }
            }
            if ($found == 0)
                continue;
        }
        if (count($eps) > 0)
        {
            $found = 0;
            foreach($eps as $fid)
            {
                if ($fid == 0) 
                {
                    $found = 1;
                    break;
                }
                $q9 = QQ("SELECT * FROM ROLES WHERE EID = ? AND UID = ?",array($fid,$r['ID']))->fetchArray();
                if ($q9 && ($uid == 0 || UserAccessEP($fid,$uid)))
                {
                    $found = 1;
                    break;
                }
            }
            if ($found == 0)
                continue;

        }

        if ($uid && UserAccessUser($r['ID'],$uid) == 0)
            continue;


        if (in_array($r['ID'],$sel))
            $s .= sprintf('<option value="%s" selected>%s %s</option>',$r['ID'],$r['LASTNAME'],$r['FIRSTNAME']);
        else
            $s .= sprintf('<option value="%s">%s %s</option>',$r['ID'],$r['LASTNAME'],$r['FIRSTNAME']);
    }
    $s .= '</select>';
    return $s;
}

require_once "access.php";


function TopFolderType($fid)
{
    $r = QQ("SELECT * FROM FOLDERS WHERE ID = ?",array($fid))->fetchArray();
    if (!$r)
        return 0;
    if ($r['PARENT'])
        return TopFolderType($r['PARENT']);
    return $r['SPECIALID'];
}

function EidOrgchartStdclass($eid)
{
    $ep = new stdClass;
    $r = QQ("SELECT * FROM ENDPOINTS WHERE ID = ?",array($eid))->fetchArray();
    if (!$r)
        return $ep;
    $ep->name = $r['NAME'];
    $ep->NameEnglish = $r['NAMEEN'];
    $ep->IsSDDDNode = false;
    $ep->IsActive = true;
    $ep->Code = $r['ID'];
    $ep->Departments = array();
    if ($r['INACTIVE'] == 1)
        $ep->IsActive = false;

    $q1 = QQ("SELECT * FROM ENDPOINTS WHERE PARENT = ?",array($eid));
    while($r1 = $q1->fetchArray())
    {
        $ep->Departments[] = EidOrgchartStdclass($r1['ID']);
    }
    return $ep;
}

function OidOrgchartStdclass($oid)
{
    $a = new stdClass;
    $r = QQ("SELECT * FROM ORGANIZATIONS WHERE ID = ?",array($oid))->fetchArray();
    if (!$r)
        return $a;
    $a->Code = $r['SHDECODE'];
    $a->Name = $r['NAME'];
    $a->NameEnglish = $r['NAMEEN'];
    $a->IsSDDDNode = true;
    $a->IsActive = true;
    $a->Departments = array();

    $q1 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ? AND PARENT = 0 ORDER BY ID",array($oid));
    while($r1 = $q1->fetchArray())
    {
        $a->Departments[] = EidOrgchartStdclass($r1['ID']);
    }
    return $a;
}



function PickEP($name = "eid",$sel = array(),$m = 0,$foruonly = 0,$readonly = 0,$oidx = 0,$andnone = 0,$req_level = 1)
{
    $s = sprintf('<select type="select" name="%s" class="input chosen-select" %s>',$name,$m  == 1 ? "multiple" : "");
    $q = QQ("SELECT * FROM ENDPOINTS");
    if ($oidx != 0)
        $q = QQ("SELECT * FROM ENDPOINTS WHERE OID = ?",array($oidx));
    if ($andnone == 1)
        {
            if (in_array(0,$sel))   
                $s .= sprintf('<option value="0" selected>(Κανένα)</option>');
            else
                $s .= sprintf('<option value="0">(Κανένα)</option>');
        }
    if ($andnone == 2)
        {
            if (in_array(0,$sel))   
                $s .= sprintf('<option value="0" selected>(Όλα)</option>');
            else
                $s .= sprintf('<option value="0">(Όλα)</option>');
        }
    while($r = $q->fetchArray())
    {
        if ($foruonly > 0 && UserAccessEP($r['ID'],$foruonly) < $req_level)
            continue;
        $fr = FRow($r['OID']);
        if (in_array($r['ID'],$sel))
            $s .= sprintf('<option value="%s" selected>%s &mdash; %s</option>',$r['ID'],$fr['NAME'],$r['NAME']);
        else
            $s .= sprintf('<option value="%s" %s>%s &mdash; %s</option>',$r['ID'],$readonly == 1 ? "disabled" : "",$fr['NAME'],$r['NAME']);
    }
    $s .= '</select>';
    return $s;
}

function PickOrganization($name = "oid",$sel = array(),$m = 0,$foruonly = 0,$readonly = 0,$andall = 1,$levr = 1)
{
    $s = sprintf('<select type="select" name="%s" class="input chosen-select" %s>',$name,$m  == 1 ? "multiple" : "");
    if ($andall == 1)
        {
            if (!in_array(0,$sel))   
                $s .= sprintf('<option value="0">(Όλα)</option>');
            else
                $s .= sprintf('<option value="0" selected>(Όλα)</option>');
        }
    $q = QQ("SELECT * FROM ORGANIZATIONS");
    while($r = $q->fetchArray())
    {
        if ($foruonly > 0 && UserAccessOID($r['ID'],$foruonly) < $levr  )
            continue;
        if (in_array($r['ID'],$sel))
            $s .= sprintf('<option value="%s" selected>%s</option>',$r['ID'],$r['NAME']);
        else
            $s .= sprintf('<option value="%s" %s>%s</option>',$r['ID'],$readonly == 1 ? "disabled" : "",$r['NAME']);
    }
    $s .= '</select>';
    return $s;
}

function nop()
{

}

function CategoryFromID($id)
{
    if ($id == 1) return "Απόφαση";
    if ($id == 2) return "Σύμβαση";
    if ($id == 3) return "Λογαριασμός";
    if ($id == 4) return "Ανακοίνωση";
    if ($id == 5) return "Άλλο";
    return "";
}
require_once "doctr.php";

function LastMsgID($did)
{
    $r1 = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($did))->fetchArray();
    if (!$r1)
        return 0;
    return $r1['ID'];
}

function OidsThatCanAccess($uid)
{
    $q1 = QQ("SELECT * FROM ORGANIZATIONS ORDER BY NAME ASC");
    $ids = array();
    while($r1 = $q1->fetchArray())
    {
        $a1 = UserAccessOID($r1['ID'],$uid);
        if ($a1 == 0)
            continue;
        $ids[] = $r1;
    }
    return $ids;
}

function NumDocsInFolder($fid,$unread = 0)
{
    if ($unread)
        return CountDB("DOCUMENTS WHERE FID = ? AND READSTATE = 1",array($fid));
    else
        return CountDB("DOCUMENTS WHERE FID = ?",array($fid));
}

function NumFoldersInFolder($fid)
{
    return CountDB("FOLDERS WHERE PARENT = ?",array($fid));
}


function TreeLocker($lid,$uid,$mobile)
{
    $lr = LockerRow($lid);
    $fis = '';

    if ($lr == null)
        return $fis;
    if (!$mobile)
        $fis .= sprintf('<li style="margin-left:2px; padding-left: 0px;" class="feid" id="feidl%s" ><span class="treecaret"></span><a href="#">%s</a>',$lid,$lr['NAME']);
    if (!$mobile)
        $fis .= '<ul class="treenested">';
    $q1 = QQ("SELECT * FROM FOLDERS WHERE LID = ? ORDER BY NAME ASC",array($lid));
    while($r1 = $q1->fetchArray())
    {
        if ($mobile)
        {
            $fis .= sprintf('<option %s value="eggr.php?fid=%s">%s</option>','',$r1['ID'],$r1['NAME']);
        }
        else
        {
            $fis .= sprintf('<li  style="margin-left:2px; padding-left: 0px;" class="feid" id="feid%s"><span class=""></span><a href="eggr.php?fid=%s">%s</a></span></li>',$r1['ID'],$r1['ID'],$r1['NAME']);
        }        
    }

    if (!$mobile)
        $fis .= '</ul>';
    if (!$mobile)
        $fis .= '</li>';

/*    if ($mobile)
    $fis .= sprintf('<option %s value="eggr.php?fid=%s">%s</option>','',$rl['ID'],$nn);
            else
    $fis .= sprintf('<li  style="margin-left:2px; padding-left: 0px;" class="feid" id="feid%s"><span class="treecaret"></span><a href="eggr.php?oid=%s&fid=0&eid=%s">%s</a>',$r2['ID'],$r2['OID'],$r2['ID'],$nn);
*/
    return $fis;
}


function Tree2($uid,$ar = 0,$oidr = null,$eidr = null,$fidr = null,$nest = 0,$cur = array(0,0,0))
{
    global $mobile;
    $fis = '';
    if ($mobile && $nest == 0)
        $fis .= '<select id="folderpick" class="input"  onchange="javascript:handleSelect(this)">';

    if ($oidr == null)
    {
        if (!$mobile)
            $fis .= sprintf('<ul class="treetop" style="margin-left:2px; padding-left: 0px;">');
        $q1 = QQ("SELECT * FROM ORGANIZATIONS ORDER BY NAME ASC");
        while($r1 = $q1->fetchArray())
        {
            $nn = $r1['NAME'];
            $selx = '';
            if ($cur[1] == 0 && $cur[2] == 0 && $cur[0] == $r1['ID'])
            {
                $nn = '<b>'.$r1['NAME'].'</b>';
                $selx = ' selected ';
            }
            $a1 = UserAccessOID($r1['ID'],$uid);
            if ($a1 < $ar)
                continue;
            if ($mobile)
                $fis .= sprintf('<option %s value="eggr.php?oid=%s">%s %s</option>',$selx,$r1['ID'],NestX('-',$nest),$nn);
            else
                $fis .= sprintf('<li style="margin-left:2px; padding-left: 0px;"><span class="treecaret"><a href="eggr.php?oid=%s">%s</a></span>',$r1['ID'],$nn);
            $fis .= Tree($uid,$ar,$r1,null,null,$nest + 1,$cur);
            $fis .= '</li>';
        }
        if (!$mobile)
            {
                if ($nest == 0)
                {
                    // Lockers
                    $ql = QQ("SELECT * FROM USERSINLOCKER WHERE UID = ?",array($uid));
                    while($rl = $ql->fetchArray())
                    {
                        $fis .= TreeLocker($rl['LID'],$uid,$mobile);
                    }
                }
                $fis .= '</ul>';
            }
    }
    else
    {
        if ($eidr == null)
        {
            if (!$mobile)
                $fis .= sprintf('<ul class="treeactive">');
            $q2 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ? ORDER BY NAME ASC",array($oidr['ID']));
            while($r2 = $q2->fetchArray())
            {
                $nn = $r2['NAME'];
                $selx = '';
                if ($cur[1] == $r2['ID'] && $cur[2] == 0 && $cur[0] == $r2['OID'])
                {
                    $nn = '<b>'.$r2['NAME'].'</b>';
                    $selx = ' selected ';
                }
                $a2 = UserAccessEP($r2['ID'],$uid);
                if ($a2 < $ar)
                    continue;
                if ($mobile)
                    $fis .= sprintf('<option %s value="eggr.php?oid=%s&fid=0&eid=%s">%s%s</option>',$selx,$r2['OID'],$r2['ID'],NestX('-',$nest),$nn);
                else
                    $fis .= sprintf('<li  style="margin-left:2px; padding-left: 0px;" class="feid" id="feid%s"><span class="treecaret"></span><a href="eggr.php?oid=%s&fid=0&eid=%s">%s</a>',$r2['ID'],$r2['OID'],$r2['ID'],$nn);
                $fis .= Tree($uid,$ar,$oidr,$r2,null,$nest + 1,$cur);
                $fis .= '</li>';
            }
            if (!$mobile)
            {
                if ($nest == 0)
                {
                    // Lockers
                    $ql = QQ("SELECT * FROM USERSINLOCKER WHERE UID = ?",array($uid));
                    while($rl = $ql->fetchArray())
                    {
                        $fis .= TreeLocker($rl['LID'],$uid,$mobile);
                    }
                }
                $fis .= '</ul>';
            }
                
        }
        else
        {
            //if ($fidr == null)
            {
                if (!$mobile && $fidr == null)
                    $fis .= sprintf('<ul class="treenested">');

                $par5 = 0;
                if ($fidr == null)
                    $q3 = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND (PARENT = 0 OR PARENT IS NULL) ORDER BY NAME ASC",array($eidr['ID']));
                else
                    {
                        $par5 = 1;
                        $q3 = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND PARENT = ? ORDER BY NAME ASC",array($eidr['ID'],$fidr['ID']));
                    }
                while($r3 = $q3->fetchArray())
                {

                    $nn = $r3['NAME'];
                    $selx = '';
                    if ($cur[2] == $r3['ID'])
                    {
                        $nn = '<b>'.$r3['NAME'].'</b>';
                        $selx = ' selected ';
                    }
                    else
                    {
                        $qUnread = CountDB("DOCUMENTS WHERE FID = ? AND READSTATE = 1",array($r3['ID']));
                        if ($qUnread)
                            $nn = '<b>'.$r3['NAME'].'</b>';
                    }
                    $a3 = UserAccessFolder($r3['ID'],$uid);
                    if ($a3 < $ar)
                        continue;
                    
                    $nd = NumDocsInFolder($r3['ID']);
                    if ($nd > 0)
                        $nn .= sprintf(' (%s) ',$nd);

                    if ($mobile)
                        $fis .= sprintf('<option %s value="eggr.php?oid=%s&eid=%s&fid=%s">%s%s <font color="red">%s</font></option>',$selx,$oidr['ID'],$eidr['ID'], $r3['ID'],NestX('-',$nest),$nn,ClassificationString2($r3['CLASSIFIED']));
                    else
                        {
                            if ($fidr)
                                {
                                    $ne = $nest - 2;
                                    $jx = '';
                                    while($ne > 0)
                                        {
                                            $jx .= '&nbsp;';
                                            $ne--;
                                        }
                                        $nn = $jx.$nn;
                                }
                            $fis .= sprintf('<li  style="margin-left:2px; padding-left: 0px;"><a href="eggr.php?oid=%s&eid=%s&fid=%s">%s <font color="red">%s</font></a></span>',$oidr['ID'],$eidr['ID'],$r3['ID'],$nn,ClassificationString2($r3['CLASSIFIED']));
                        }
                    $fis .= Tree($uid,$ar,$oidr,$eidr,$r3,$nest + 1,$cur);
                    $fis .= '</li>';
                }
                if (!$mobile && $fidr == null)
                    $fis .= '</ul>';
            }
            }
        }


    if ($nest == 0 && $mobile)
    {
        $fis .= '</select><br><br>';
        $fis .= '
        <script>

        function handleSelect(elm)
        {
            if (elm.value)
               window.location = elm.value;
        }
        </script>';
    }

    if ($nest == 0 && !$mobile)
    $fis .= '
    <script>
    $(".treecaret").click(function()
        {
            var lie = $(this).parent();
            var ul = $(":nth-child(3)",lie);
            if (ul.hasClass("treenested"))
            {
                setCookie("treecookie_" + lie.prop("id"),"1",1);
                $(this).addClass("treecaret-down");
                ul.addClass("treeactive");
                ul.removeClass("treenested");
            }
            else
            {
                deleteCookie("treecookie_" + lie.prop("id"));
                $(this).removeClass("treecaret-down");
                ul.removeClass("treeactive");
                ul.addClass("treenested");
            }
        });

    $(".feid").each(function()
        {
            var li = $(this);
            var prop = li.prop("id");
            var coo = getCookie("treecookie_" + prop);
            if (coo == "1")
                {
                var sy = $(":nth-child(1)",li);
                var ul = $(":nth-child(3)",li);
                sy.addClass("treecaret-down");
                ul.addClass("treeactive");
                ul.removeClass("treenested");
                }
        }
    );
    
    </script>';
    return $fis;
}


function Tree($uid,$ar = 0,$oidr = null,$eidr = null,$fidr = null,$nest = 0,$cur = array(0,0,0))
{
    global $mobile;
    if ($mobile)
        return Tree2($uid,$ar,$oidr,$eidr,$fidr,$nest,$cur);
    $fis = '';
    if ($nest == 0)
        $fis = '<aside class="menu">';
    if ($oidr == null)
    {
        $q1 = QQ("SELECT * FROM ORGANIZATIONS ORDER BY NAME ASC");
        while($r1 = $q1->fetchArray())
        {
            $nn = $r1['NAME'];
            $selx = '';
            if ($cur[1] == 0 && $cur[2] == 0 && $cur[0] == $r1['ID'])
            {
                $nn = '<b>'.$r1['NAME'].'</b>';
                $selx = ' selected ';
            }
            $a1 = UserAccessOID($r1['ID'],$uid);
            if ($a1 < $ar)
                continue;
            $fis .= sprintf('<p class="menu-label" id=""><a href="eggr.php?oid=%s">%s</a></p>',$r1['ID'],$nn);
            $fis .= Tree($uid,$ar,$r1,null,null,$nest + 1,$cur);
        }
/*        if (!$mobile)
            {
                if ($nest == 0)
                {
                    // Lockers
                    $ql = QQ("SELECT * FROM USERSINLOCKER WHERE UID = ?",array($uid));
                    while($rl = $ql->fetchArray())
                    {
                        $fis .= TreeLocker($rl['LID'],$uid,$mobile);
                    }
                }
                $fis .= '</ul>';
            }
*/            
    }
    else
    {
        if ($eidr == null)
        {
            $fis .= sprintf('<ul class="menu-list">');
            $q2 = QQ("SELECT * FROM ENDPOINTS WHERE OID = ? ORDER BY NAME ASC",array($oidr['ID']));
            while($r2 = $q2->fetchArray())
            {
                $nn = $r2['NAME'];
                $selx = '';
                if ($cur[1] == $r2['ID'] && $cur[2] == 0 && $cur[0] == $r2['OID'])
                {
                    $nn = '<b>'.$r2['NAME'].'</b>';
                    $selx = ' selected ';
                }
                $a2 = UserAccessEP($r2['ID'],$uid);
                if ($a2 < $ar)
                    continue;

                if(!isset($_COOKIE[sprintf("left_menu_eid_%s",$r2['ID'])]))
                    $fis .= sprintf('<li><a class="feid%s" href="eggr.php?oid=%s&fid=0&eid=%s&setc=left_menu_eid_%s">+ %s</a>',$r2['ID'],$r2['OID'],$r2['ID'],$r2['ID'],$nn);
                else
                {
                    $fis .= sprintf('<li><a class="feid%s" href="eggr.php?oid=%s&fid=0&eid=%s&remc=left_menu_eid_%s">- %s</a>',$r2['ID'],$r2['OID'],$r2['ID'],$r2['ID'],$nn);
                    $fis .= Tree($uid,$ar,$oidr,$r2,null,$nest + 1,$cur);
                }

                $fis .= '</li>';

            }
        }
        else
        {
                $par5 = 0;
                if ($fidr == null)
                    $q3 = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND (PARENT = 0 OR PARENT IS NULL) ORDER BY NAME ASC",array($eidr['ID']));
                else
                    {
                        $par5 = 1;
                        $q3 = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND PARENT = ? ORDER BY NAME ASC",array($eidr['ID'],$fidr['ID']));
                    }
                $fis .= '<ul>';
                while($r3 = $q3->fetchArray())
                {
                    $nn = $r3['NAME'];
                    $selx = '';
                    if ($cur[2] == $r3['ID'])
                    {
                        $nn = '<b>'.$r3['NAME'].'</b>';
                        $selx = ' selected ';
                    }
                    else
                    {
                        $qUnread = CountDB("DOCUMENTS WHERE FID = ? AND READSTATE = 1",array($r3['ID']));
                        if ($qUnread)
                            $nn = '<b>'.$r3['NAME'].'</b>';
                    }
                    $a3 = UserAccessFolder($r3['ID'],$uid);
                    if ($a3 < $ar)
                        continue;
                    
                    $nd = NumDocsInFolder($r3['ID']);
                    if ($nd > 0)
                        $nn .= sprintf(' (%s) ',$nd);

                    if ($fidr)
                        {
                            $ne = $nest - 2;
                            $jx = '';
                            while($ne > 0)
                                {
                                    $jx .= '&nbsp;';
                                    $ne--;
                                }
                                $nn = $jx.$nn;
                        }

                    $subf = NumFoldersInFolder($r3['ID']);

                    if ($subf == 0)
                    {
                        $fis .= sprintf('<li><a href="eggr.php?oid=%s&eid=%s&fid=%s">%s<font color="red">%s</font></a>',$oidr['ID'],$eidr['ID'],$r3['ID'],$nn,ClassificationString2($r3['CLASSIFIED']));
                    }
                    else
                    if(!isset($_COOKIE[sprintf("left_menu_fid_%s",$r3['ID'])]))
                        $fis .= sprintf('<li><a href="eggr.php?oid=%s&eid=%s&fid=%s&setc=left_menu_fid_%s">+ %s<font color="red">%s</font></a>',$oidr['ID'],$eidr['ID'],$r3['ID'],$r3['ID'],$nn,ClassificationString2($r3['CLASSIFIED']));
                    else
                    {
                        $fis .= sprintf('<li><a href="eggr.php?oid=%s&eid=%s&fid=%s&remc=left_menu_fid_%s">- %s<font color="red">%s</font></a>',$oidr['ID'],$eidr['ID'],$r3['ID'],$r3['ID'],$nn,ClassificationString2($r3['CLASSIFIED']));
                        $fis .= Tree($uid,$ar,$oidr,$eidr,$r3,$nest + 1,$cur);
                    }
                    $fis .= '</li>';
					
                }
                $fis .= '</ul>';
            
        }

    }
      if ($nest == 0)
            $fis .= '</aside>';
      return $fis;
}



function EchoShdePicker($id,$name,$value = array(),$active = 1,$restr = array())
{
    $resti = '';
    if (count($restr))
        $resti = implode(",",$restr);
    $s = sprintf('<dialog id="dialogx_%s" >  
    </dialog>
     <script>
      function shdepickreturn_%s(items,val){
        var dialog = document.getElementById("dialogx_%s");
        dialog.close();
        var str = items.toString();
        $(\'#\' + val).val(str);
        $(\'#\' + "shdepickbutton_%s").text("Επιλογή: " + items.length);
      }
      function PickSide_%s(control_id)
      {
        var dialog = document.getElementById("dialogx_%s");
        var val = $(\'#\' + control_id).val();
        var dialog2 = $("#dialogx_%s");
        block();
        $.ajax({
                        url: "shdepick.php",
                        method: "GET",
                        data: {"from" : val,"val" : control_id, "function" : "shdepickreturn_%s","active" : %s,"restr" : "%s"},
                        success: function (result) {
                            unblock();
                            dialog2.html(result);
                            dialog.showModal();
                        }
                    });
      }
      </script>
      <input type="hidden" name="%s" id="shdepick_%s" value="%s"> <button type="button" class="button is-link" id="shdepickbutton_%s" onclick="PickSide_%s(\'shdepick_%s\');">Επιλογή: %s</button>
      ',$id,$id,$id,$id,$id,$id,$id,$id,$active,$resti,$name,$id,count($value) == 0 ? "" : implode(",",$value),$id,$id,$id,count($value));
    
      return $s;
}

function ed( $inp,$key, $action = 'e' ) 
{
    if ($inp == "")
        return "";

    if ($key == "")
        return $inp;

    $cipher = "aes-128-cbc";
    if ($action == 'e')
    {
        $ciphertext_raw = openssl_encrypt($inp, $cipher, $key, $options=OPENSSL_RAW_DATA);
        $ciphertext = base64_encode( $ciphertext_raw );
        return $ciphertext;
    }
    else
    {
        $ciphertext = base64_decode($inp);
        $original_plaintext = openssl_decrypt($ciphertext, $cipher, $key, $options=OPENSSL_RAW_DATA);
        return $original_plaintext;
    }

/*    $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
    if ($action == 'e')
    {
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($inp, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        $ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
        return $ciphertext;
    }
    else
    {
        $c = base64_decode($inp);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len=32);
        $ciphertext_raw = substr($c, $ivlen+$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        if ($calcmac === FALSE || $hmac == "")
            return "";
        if (hash_equals($hmac, $calcmac))// timing attack safe comparison
            return  $original_plaintext;
        return "";
    }
*/
    // you may change these values to your own
    /*    $secret_iv = 'my_simple_secret_iv';

    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash( 'sha256', $secret_key );
    $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

    if( $action == 'e' ) {
        $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }
    else if( $action == 'd' ){
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    }

    return $output;
*/
}

function guidv4()
{
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function PasswordFromSession($id)
{
    $must = sprintf("shde_pwd_%s",$id);
    if (!array_key_exists($must,$_SESSION))
        return FALSE;
    return $_SESSION[$must];
}

function mime_type($filename,$rev = 0) {

    $mime_types = array(
       'txt' => 'text/plain',
       'htm' => 'text/html',
       'html' => 'text/html',
       'css' => 'text/css',
       'json' => array('application/json', 'text/json'),
       'xml' => 'application/xml',
       'swf' => 'application/x-shockwave-flash',
       'flv' => 'video/x-flv',
  
       'hqx' => 'application/mac-binhex40',
       'cpt' => 'application/mac-compactpro',
       'csv' => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
       'bin' => 'application/macbinary',
       'dms' => 'application/octet-stream',
       'lha' => 'application/octet-stream',
       'lzh' => 'application/octet-stream',
       'exe' => array('application/octet-stream', 'application/x-msdownload'),
       'class' => 'application/octet-stream',
       'so' => 'application/octet-stream',
       'sea' => 'application/octet-stream',
       'dll' => 'application/octet-stream',
       'oda' => 'application/oda',
       'ps' => 'application/postscript',
       'smi' => 'application/smil',
       'smil' => 'application/smil',
       'mif' => 'application/vnd.mif',
       'wbxml' => 'application/wbxml',
       'wmlc' => 'application/wmlc',
       'dcr' => 'application/x-director',
       'dir' => 'application/x-director',
       'dxr' => 'application/x-director',
       'dvi' => 'application/x-dvi',
       'gtar' => 'application/x-gtar',
       'gz' => 'application/x-gzip',
       'php' => 'application/x-httpd-php',
       'php4' => 'application/x-httpd-php',
       'php3' => 'application/x-httpd-php',
       'phtml' => 'application/x-httpd-php',
       'phps' => 'application/x-httpd-php-source',
       'js' => array('application/javascript', 'application/x-javascript'),
       'sit' => 'application/x-stuffit',
       'tar' => 'application/x-tar',
       'tgz' => array('application/x-tar', 'application/x-gzip-compressed'),
       'xhtml' => 'application/xhtml+xml',
       'xht' => 'application/xhtml+xml',             
       'bmp' => array('image/bmp', 'image/x-windows-bmp'),
       'gif' => 'image/gif',
       'jpeg' => array('image/jpeg', 'image/pjpeg'),
       'jpg' => array('image/jpeg', 'image/pjpeg'),
       'jpe' => array('image/jpeg', 'image/pjpeg'),
       'png' => array('image/png', 'image/x-png'),
       'tiff' => 'image/tiff',
       'tif' => 'image/tiff',
       'shtml' => 'text/html',
       'text' => 'text/plain',
       'log' => array('text/plain', 'text/x-log'),
       'rtx' => 'text/richtext',
       'rtf' => 'text/rtf',
       'xsl' => 'text/xml',
       'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
       'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
       'word' => array('application/msword', 'application/octet-stream'),
       'xl' => 'application/excel',
       'eml' => 'message/rfc822',
  
       // images
       'png' => 'image/png',
       'jpe' => 'image/jpeg',
       'jpeg' => 'image/jpeg',
       'jpg' => 'image/jpeg',
       'gif' => 'image/gif',
       'bmp' => 'image/bmp',
       'ico' => 'image/vnd.microsoft.icon',
       'tiff' => 'image/tiff',
       'tif' => 'image/tiff',
       'svg' => 'image/svg+xml',
       'svgz' => 'image/svg+xml',
  
       // archives
       'zip' => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
       'rar' => 'application/x-rar-compressed',
       'msi' => 'application/x-msdownload',
       'cab' => 'application/vnd.ms-cab-compressed',
  
       // audio/video
       'mid' => 'audio/midi',
       'midi' => 'audio/midi',
       'mpga' => 'audio/mpeg',
      'mp2' => 'audio/mpeg',
       'mp3' => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
       'aif' => 'audio/x-aiff',
       'aiff' => 'audio/x-aiff',
       'aifc' => 'audio/x-aiff',
       'ram' => 'audio/x-pn-realaudio',
       'rm' => 'audio/x-pn-realaudio',
       'rpm' => 'audio/x-pn-realaudio-plugin',
       'ra' => 'audio/x-realaudio',
       'rv' => 'video/vnd.rn-realvideo',
       'wav' => array('audio/x-wav', 'audio/wave', 'audio/wav'),
       'mpeg' => 'video/mpeg',
       'mpg' => 'video/mpeg',
       'mpe' => 'video/mpeg',
       'qt' => 'video/quicktime',
       'mov' => 'video/quicktime',
       'avi' => 'video/x-msvideo',
       'movie' => 'video/x-sgi-movie',
  
       // adobe
       'pdf' => 'application/pdf',
       'psd' => array('image/vnd.adobe.photoshop', 'application/x-photoshop'),
       'ai' => 'application/postscript',
       'eps' => 'application/postscript',
       'ps' => 'application/postscript',
  
       // ms office
       'doc' => 'application/msword',
       'rtf' => 'application/rtf',
       'xls' => array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
       'ppt' => array('application/powerpoint', 'application/vnd.ms-powerpoint'),
  
       // open office
       'odt' => 'application/vnd.oasis.opendocument.text',
       'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
  
    if ($rev == 1)
    {
        foreach($mime_types as $k=>$v)
        {
            if (is_array($v))
            {
                foreach($v as $vv)
                {
                    if ($vv == $filename)
                        return $k;
                }
            }
            else
            if ($v == $filename)
                return $k;
        }
        return "";
    }
    $ext = explode('.', $filename);
    $ext = strtolower(end($ext));
   
    if (array_key_exists($ext, $mime_types)) {
      return (is_array($mime_types[$ext])) ? $mime_types[$ext][0] : $mime_types[$ext];
    } else if (function_exists('finfo_open')) {
       if(file_exists($filename)) {
         $finfo = finfo_open(FILEINFO_MIME);
         $mimetype = finfo_file($finfo, $filename);
         finfo_close($finfo);
         return $mimetype;
       }
    }
   
    return 'application/octet-stream';
  }

function DeleteMessage($mid,$fc = 0)
{
    global $u;
    if (UserAccessMessage($mid,$u->uid) != 2 && $fc != 1)
    {
        return false;
    }        

    $msg = MRow($mid,1);
    $did = $msg['DID'];

    if (QQ("SELECT * FROM MESSAGES WHERE DID = ? AND DATE > ?",array($did,$msg['DATE']))->fetchArray())
    {
        // it's not the latest
        return false;
    }
    $cnt = CountDB("MESSAGES WHERE DID = ?",array($did));
    if ($cnt == 1 && QQ("SELECT * FROM MESSAGES WHERE ID = ?",array($mid))->fetchArray())
    {
        return DeleteDocument($did,$fc);
    }

    QQ("BEGIN TRANSACTION");
    QQ("DELETE FROM MESSAGES WHERE ID = ?",array($mid));
    QQ("DELETE FROM ATTACHMENTS WHERE MID = ?",array($mid));
    if (!QQ("SELECT * FROM MESSAGES WHERE DID = ?",array($did))->fetchArray())
    {
        QQ("DELETE FROM DOCUMENTS WHERE ID = ?",array($did));
    }
    QQ("COMMIT");
    return true;
}

function DeleteDocument($did,$fc = 0)
{
    global $u;
    if (UserAccessDocument($did,$u->uid) != 2 && $fc == 0)
    {
        return false;
    }        
    $docr = DRow($did);


    // classified?
/*    $docr = DRow($did);
    if ($docr == null)
        return false;
    if ($docr['ENCRYPTED'] && $docr['ENCRYPTED'] == 1)
        return false;
*/

    // Folder
    $fid = $docr['FID'];
    $topf = TopFolderType($fid);
    if ($topf != FOLDER_TRASH)
    {
        $fid2 = QQ("SELECT * FROM FOLDERS WHERE EID = ? AND SPECIALID = ?",array($docr['EID'],FOLDER_TRASH))->fetchArray();
        if ($fid2)
        {
        QQ("UPDATE DOCUMENTS SET FID = ?,ENTRYCREATED = ? WHERE ID = ?",array($fid2['ID'],time(),$did));
        return true;        
        }
    }

    QQ("BEGIN TRANSACTION");
    $q1 = QQ("SELECT * FROM MESSAGES WHERE DID = ?",array($did));
    while($r1 = $q1->fetcharray())
        QQ("DELETE FROM ATTACHMENTS WHERE MID = ?",array($r1['ID']));

    QQ("DELETE FROM MESSAGES WHERE DID = ?",array($did));
    QQ("DELETE FROM DOCUMENTS WHERE ID = ?",array($did));
    QQ("COMMIT");
    return true;
}

function DocumentDecrypt(&$dr)
{
    if (!$dr)
        return;
    // comment?
    if (array_key_exists("COMMENT",$dr))
    {
        // Message
        $doc = DRow($dr['DID']);
        if (!$doc)
            return;
        if ($doc['CLASSIFIED'] == 0)
            return;
        $pwd = PasswordFromSession($doc['ID']);
        if ($pwd === FALSE)
        {
            $dr['INFO'] = 'ENCRYPTED';
            $dr['MSG'] = 'ENCRYPTED';
            if ($dr['SIGNEDPDF'] && strlen($dr['SIGNEDPDF']) > 5)
               $dr['SIGNEDPDF'] = 'XXXXXXXXX';
            $dr['ENCRYPTED'] = 1;
            return;
        }
        $dr['COMMENT'] = ed($dr['COMMENT'],$pwd,'d');
        return;

    }

    // document?
    if (array_key_exists("MSG",$dr) && array_key_exists("INFO",$dr))
    {
        // Message
        $doc = DRow($dr['DID']);
        if (!$doc)
            return;
        if ($doc['CLASSIFIED'] == 0)
            return;
        $pwd = PasswordFromSession($doc['ID']);
        if ($pwd === FALSE)
        {
            $dr['INFO'] = 'ENCRYPTED';
            $dr['MSG'] = 'ENCRYPTED';
            if ($dr['SIGNEDPDF'] && strlen($dr['SIGNEDPDF']) > 5)
               $dr['SIGNEDPDF'] = 'XXXXXXXXX';
            $dr['ENCRYPTED'] = 1;
            return;
        }
        $dr['INFO'] = ed($dr['INFO'],$pwd,'d');
        $dr['MSG'] = ed($dr['MSG'],$pwd,'d');
        return;
    }
    

    if (!array_key_exists("CLASSIFIED",$dr))
        return;

    if ($dr['CLASSIFIED'] == 0)
        return;

    $pwd = PasswordFromSession($dr['ID']);
    if ($pwd === FALSE)
        {
            $dr['TOPIC'] = sprintf('<a href="decrypt.php?did=%s">ENCRYPTED</a>',$dr['ID']);
            $dr['ENCRYPTED'] = 1;
            $dr['RECPX'] = '';
            $dr['RECPY'] = '';
            $dr['RECPZ'] = '';
            $dr['KOINX'] = '';
            $dr['KOINY'] = '';
            $dr['KOINZ'] = '';
            $dr['BCCX'] = '';
            $dr['BCCY'] = '';
            $dr['BCCZ'] = '';
            $dr['ESWX'] = '';
        return;
        }

    // Test if valid
    if (ed($dr['TOPIC'],$pwd,'d') == "")
    {
        $must = sprintf("shde_pwd_%s",$dr['ID']);
        unset($_SESSION[$must]);
        $dr['TOPIC'] = sprintf('<a href="decrypt.php?did=%s">ENCRYPTED</a>',$dr['ID']);
        $dr['ENCRYPTED'] = 1;
        return;
       }
    $dr['TOPIC'] = ed($dr['TOPIC'],$pwd,'d');
    $dr['RECPX'] = ed($dr['RECPX'],$pwd,'d');
    $dr['RECPY'] = ed($dr['RECPY'],$pwd,'d');
    $dr['RECPZ'] = ed($dr['RECPZ'],$pwd,'d');
    $dr['KOINX'] = ed($dr['KOINX'],$pwd,'d');
    $dr['KOINY'] = ed($dr['KOINY'],$pwd,'d');
    $dr['KOINZ'] = ed($dr['KOINZ'],$pwd,'d');
    $dr['BCCX'] = ed($dr['BCCX'],$pwd,'d');
    $dr['BCCY'] = ed($dr['BCCY'],$pwd,'d');
    $dr['BCCZ'] = ed($dr['BCCZ'],$pwd,'d');
    $dr['ESWX'] = ed($dr['ESWX'],$pwd,'d');
}

function DeleteWholeMessage($id)
{
    QQ("DELETE FROM ATTACHMENTS WHERE MID = ?",array($id));
    QQ("DELETE FROM MESSAGES WHERE ID = ?",array($id));
}

function DeleteWholeDocument($id)
{
    $q5 = QQ("SELECT ID FROM MESSAGES WHERE DID = ?",array($id));
    $ids = array();
    while($r5 = $q5->fetchArray())
    {
        $ids[] = $r5['ID'];
    }
    foreach($ids as $id2)
    {
        DeleteWholeMessage($id2);
    }
    QQ("DELETE FROM DOCUMENTS WHERE ID = ?",array($id));
}

function DeleteWholeFolder($id)
{
    $q5 = QQ("SELECT ID FROM DOCUMENTS WHERE FID = ?",array($id));
    $ids = array();
    while($r5 = $q5->fetchArray())
    {
        $ids[] = $r5['ID'];
    }
    foreach($ids as $id2)
    {
        DeleteWholeDocument($id2);
    }
    QQ("DELETE FROM FOLDERS WHERE ID = ?",array($id));
}


function DeleteWholeLocker($id)
{
    $fq = QQ("SELECT * FROM FOLDERS WHERE LID = ?",array($id));
    while($r = $fq->fetchArray())
    {
        $q5 = QQ("SELECT ID FROM DOCUMENTS WHERE FID = ?",array($r['ID']));
        $ids = array();
        while($r5 = $q5->fetchArray())
        {
            $ids[] = $r5['ID'];
        }
        foreach($ids as $id2)
        {
            DeleteWholeDocument($id2);
        }
        QQ("DELETE FROM FOLDERS WHERE ID = ?",array($r['ID']));
    }
    QQ("DELETE FROM LOCKERS WHERE ID = ?",array($id));
}


function DeleteWholeEndpoint($id)
{
    $q5 = QQ("SELECT ID FROM DOCUMENTS WHERE EID = ?",array($id));
    $ids = array();
    while($r5 = $q5->fetchArray())
    {
        $ids[] = $r5['ID'];
    }
    foreach($ids as $id2)
        DeleteWholeDocument($id2);

    $q6 = QQ("SELECT ID FROM FOLDERS WHERE EID = ?",array($id));
    $ids2 = array();
    while($r6 = $q6->fetchArray())
    {
        $ids2[] = $r6['ID'];
    }
    foreach($ids2 as $id3)
        DeleteWholeFolder($id3);

    $q7 = QQ("SELECT ID FROM LOCKERS WHERE EID = ?",array($id));
    $ids7 = array();
    while($r7 = $q7->fetchArray())
    {
        $ids7[] = $r7['ID'];
    }
    foreach($ids7 as $id77)
        DeleteWholeLocker($id77);
    
    
    QQ("DELETE FROM ADDRESSBOOK WHERE EID = ?",array($id));
    QQ("DELETE FROM ROLES WHERE EID = ?",array($id));
    QQ("DELETE FROM ENDPOINTS WHERE ID = ?",array($id))->fetchArray();
}


function DeleteWholeOrganization($id)
{
    $q5 = QQ("SELECT ID FROM ENDPOINTS WHERE OID = ?",array($id));
    $ids = array();
    while($r5 = $q5->fetchArray())
    {
        $ids[] = $r5['ID'];
    }
    foreach($ids as $id2)
        DeleteWholeEndpoint($id2);

    QQ("DELETE FROM ADDRESSBOOK WHERE OID = ?",array($id));
    QQ("DELETE FROM ROLES WHERE OID = ?",array($id));
    QQ("DELETE FROM ORGANIZATIONS WHERE ID = ?",array($id))->fetchArray();
}

function IsProist($uid)
{
    $r1 = QQ("SELECT EID FROM ROLES WHERE UID = ? AND ROLEID = ?",array($uid,ROLE_SIGNER0))->fetchArray();
    if ($r1)
    {
        return $r1['EID'];
    }
    return 0;
}

function GetProist($uid)
{
    if (!$uid)
        return 0;
    $r1 = QQ("SELECT * FROM ROLES WHERE UID = ? AND ROLEID = ?",array($uid,ROLE_SIGNER0))->fetchArray();
    if ($r1)
    {
        $epr = EPRow($r1['EID']);
        if (!$epr)
            return 0;
        if ($epr['PARENT'] == 0)
            return 0;
        
        $r2 = QQ("SELECT * FROM ROLES WHERE EID = ? AND ROLEID = ?",array($epr['PARENT'],ROLE_SIGNER0))->fetchArray();
        if (!$r2)
            return 0;
        return $r2['UID'];
    }
    $r1 = QQ("SELECT * FROM ROLES WHERE UID = ? AND (ROLEID = ? OR ROLEID = ?)",array($uid,ROLE_USER,ROLE_EDITOR))->fetchArray();
    if (!$r1)
        return 0;
    $r2 = QQ("SELECT * FROM ROLES WHERE EID = ? AND ROLEID = ?",array($r1['EID'],ROLE_SIGNER0))->fetchArray();
    if (!$r2)
        return 0;
    return $r2['UID'];
}


function SortArrayProist($arr)
{
    usort($arr, function ($a, $b)  {
        $pr1 = IsProist($a);
        $pr2 = IsProist($b);
        if ($pr1 == 0 && $pr2 == 0)
            return strcmp(UserRow($a)['LASTNAME'],UserRow($b)['LASTNAME']);
        if ($pr1 && $pr2 == 0)
            return 1;
        if ($pr1 == 0 && $pr2)
            return 0;

        $xa = $b;
        for(;;)
        {
            $xa2 = GetProist($xa);
            if ($xa2 == $a)
                return 1;
            if ($xa2 == 0)
                break;
            $xa = $xa2;
        }
        return 0;
    });
    return $arr;
}


function BuildSadesRequest($did)
{
    $r1 = Drow($did);
    if (!$r1)
        return "";
        
    $anyp = '';
    $anypp = sprintf('shde_pwd_%s',$r1['ID']);
    if (array_key_exists($anypp,$_SESSION))
        $anyp = $_SESSION[$anypp];
   
    $eid = $r1['EID'];
    $role = QQ("SELECT * FROM ROLES WHERE ROLEID = ? AND EID = ?",array(ROLE_SIGNER0,$eid))->fetchArray();
    if (!$role)
        return "";
    $sigs = array($role['UID']);
  
    $s2 = sprintf('<a href="shde:%s_%s_%s_1">SAdES</a>',$sigs[0],$r1['CLSID'],$anyp == "" ? "X" : base64_encode($anyp));
    
    // Extra
    if ($r1['ADDEDSIGNERS'])
    {
        $extrasigs = explode(",",$r1['ADDEDSIGNERS']);
        foreach($extrasigs as $e)
            $sigs[] = $e;
        SortArrayProist($sigs);
    }
    
    if (count($sigs) >= 2)
    {
        $s2 = '';
        $lev = 1;
        foreach($sigs as $sig)
        {
            $ur = UserRow($sig);
            $s2 .= sprintf('<a href="shde:%s_%s_%s_%s">SAdES %s</a><br>',$sig,$r1['CLSID'],$anyp == "" ? "X" : base64_encode($anyp),$lev,$ur['LASTNAME']);
            $lev++;
        }
       
    }
    return $s2;
}

function RowForOrgChart($parentcode,$subcode)
{
//    $oidr2 = QQ("SELECT * FROM ORGCHART WHERE CODE2 = ?",array($parentcode)->fetchArray();
  //  if (!$oidr2)
    //    return 0;
    $q1 = QQ(sprintf("SELECT * FROM ORGCHART WHERE CODE2 = '%s' AND CODE LIKE '%s%%'",$subcode,$parentcode))->fetchArray();
    if (!$q1)
        return 0;
    return $q1['ID'];
}

function IsIncoming($r1)
{
    if (!$r1)
        return false;
    if ($r1['FROMX'] && strlen($r1['FROMX']))
        return true;
    if ($r1['FROMY'] && strlen($r1['FROMY']))
        return true;
    if ($r1['FROMZ'] && strlen($r1['FROMZ']))
        return true;
    return false;
}

function GetBearer($oid)
{
    $authorization = '';
    $loge = sprintf("shde_login_%s",$oid);
    if (array_key_exists($loge,$_SESSION))
    {
        if (array_key_exists("AccessToken",$_SESSION[$loge]))
        {
            $authorization = "Authorization: Bearer ".$_SESSION[$loge]["AccessToken"]; // Prepare the authorisation token
        }
    }
    return $authorization;
}

