<?php


require_once "functions.php";
if (!$u)
    diez();
set_time_limit(120);
require_once "output.php";
if ($u->superadmin)
    PrintHeader('index.php','&nbsp; <button class="button is-success autobutton" href="orgchart.php?table=1">Μορφή Πίνακα</button> &nbsp; <button class="button is-link autobutton" href="orgchart.php?reload=1">Ανανέωση</button>');
else
    PrintHeader('index.php','&nbsp; <button class="button is-small is-success autobutton" href="orgchart.php?table=1">Μορφή Πίνακα</button> ');

$example_orgchart = '{
"Version": 1,
"RootNode": {
    "Code": 0,
    "Name": "Αρχή εγγραφής",
    "NameEnglish": null,
    "IsActive": true,
    "IsSDDDNode": true,
    "Departments": [],
	"ChildNodes": [
    {
    "Code": 88444,
    "Name": "ΔΗΜΟΤΙΚΗ ΚΟΙΝΩΦΕΛΗΣ ΕΠΙΧΕΙΡΗΣΗ ΔΗΜΟΥ ΖΑΧΑΡΩΣ",
    "IsActive": true,
    "IsSDDDNode": true,
    "Departments": [
    {
    "Code": 1,
    "Name": "Διεύθυνση Α",
    "IsActive": true,
    "IsSDDDNode": false,
    "Departments": [
    {
    "Code": 4,
    "Name": "Διεύθυνση Α.2",
    "IsActive": true,
    "IsSDDDNode": false,
    "Departments": [],
    "ChildNodes": []
    },
    {
    "Code": 8844401,
    "Name": "Διεύθυνση Α.1",
    "IsActive": true,
    "IsSDDDNode": true,
    "Departments": [],
    "ChildNodes": []
    }
    ],
    "ChildNodes": []
    },
    {
    "Code": 2,
    "Name": "Διεύθυνση B",
    "IsActive": true,
    "IsSDDDNode": false,
    "Departments": [
    {
    "Code": 5,
    "Name": "Διεύθυνση B.1",
    "IsActive": false,
    "IsSDDDNode": false,
    "Departments": [],
    "ChildNodes": []
    },
    {
    "Code": 6,
    "Name": "Διεύθυνση B.2",
    "IsActive": true,
    "IsSDDDNode": false,
    "Departments": [],
    "ChildNodes": []
    }
    ],
    "ChildNodes": []
    }
    ],
    "ChildNodes": []
   }
   ]
}

}
   ';


function AddDep($j,$par,$deps = 0,$rootx = 0)
{
    global $lastRowID;
    if ($j->IsSDDDNode)
        $root = $j->Code;
    else
        $root = $rootx;
    $rootx = $root;
    $finalcode = $j->Code;
    if ($finalcode != $root)
        $finalcode = sprintf("%s|%s",$root,$j->Code);
    QQ("INSERT INTO ORGCHART (CODE,NAME,ACTIVE,SDDD,PARENT,CODE2,ROOTCODE) VALUES(?,?,?,?,?,?,?)",array(
        $finalcode,$j->Name,$j->IsActive,$j->IsSDDDNode,$par,$j->Code,$root
    ));
    $id = $lastRowID;
    foreach($j->ChildNodes as $dep)
        AddDep($dep,$id,0,$rootx);
    foreach($j->Departments as $ch)
        AddDep($ch,$id,1,$rootx);
}

function ReceiveOrgLive()
{
    global $siteroot;
    $c = curl_init();

    $q1 = QQ("SELECT * FROM ORGANIZATIONS");
    while($r1 = $q1->fetchArray())
    {
        $base = ShdeUrl($r1['ID']).'/orgchart';
        $st = $base;
    
        $loge = sprintf("shde_login_%s",$r1['ID']);
        if (array_key_exists($loge,$_SESSION))  
        {
            if (array_key_exists("AccessToken",$_SESSION[$loge]))
            {
                $authorization = "Authorization: Bearer ".$_SESSION[$loge]["AccessToken"]; // Prepare the authorisation token
                curl_setopt($c, CURLOPT_HTTPHEADER, array($authorization)); // Inject the token into the header
                break;
            }
        }
    }
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_AUTOREFERER,    1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($c, CURLOPT_URL, $st );
    curl_setopt($c, CURLOPT_REFERER, $siteroot);
    $r = curl_exec($c);
    $j = json_decode($r);
    QQ("DELETE FROM ORGCHART");
    QQ("BEGIN TRANSACTION;");
    QQ("UPDATE ORGCHART SET ACTIVE = 0");
    foreach($j->RootNode->ChildNodes as $ch)
        AddDep($ch,0,0,0);
    QQ("COMMIT");
}

if (array_key_exists("reload",$req) && $u->superadmin)
    {
        ReceiveOrgLive(0);
        QQ("BEGIN TRANSACTION;");
        $q1 = QQ("SELECT * FROM ORGCHART");
        while($r1 = $q1->fetchArray())
        {
            $fn = OrgChartFullName2($r1['ID']);
            QQ("UPDATE ORGCHART SET FULLNAME = ? WHERE ID = ?",array(implode("&mdash;",array_reverse($fn)),$r1['ID']));
        }
        QQ("COMMIT");
        redirect("orgchart.php");
        die;
    }

// Print Org
function OrgTree2($top = 0)
{
    $fis = '';
    if ($top == 0)
    {
        $fis .= sprintf('<div class="tf-tree tf-gap lg"><ul><li><span class="tf-nc">Φορείς</span><ul>');
        $q1 = QQ("SELECT * FROM ORGCHART WHERE PARENT = 0 ORDER BY NAME ASC");
        while($r1 = $q1->fetchArray())
        {
            $fis .= sprintf('<li><span class="tf-nc">%s</span>', $r1['NAME']);
            $fis .= OrgTree2($r1['ID']);
            $fis .= '</li>';
        }
        $fis .= '</ul></li></ul></div>';
    }
    else
    {
        $fis .= sprintf('<ul>');
        $q2 = QQ("SELECT * FROM ORGCHART WHERE PARENT = ? ORDER BY NAME ASC",array($top));
        while($r2 = $q2->fetchArray())
        {
            $fis .= sprintf('<li><span class="tf-nc">%s</span>',$r2['NAME']);
            $fis .= OrgTree2($r2['ID']);
            $fis .= '</li>';
        }
        $fis .= '</ul>';
    }
    return $fis;
}

function OrgTree3($top = 0)
{
    global $req;
    $fis = '<style>
    li {
        font-size: 14px;
        margin-left: 10px;
        list-style-type: circle;
     }
     </style>
     <div class="content">';

     $mustActive = 0;
     if (array_key_exists("active",$req))
        $mustActive = 1;

    if ($top == 0)
    {
        $q1 = QQ("SELECT * FROM ORGCHART WHERE PARENT = 0 ORDER BY NAME ASC");
        while($r1 = $q1->fetchArray())
        {
            $n = $r1['NAME'];
            if ($r1['ACTIVE'] == 1)
                $n = sprintf('<b>%s</b>',$r1['NAME']);
            if ($mustActive && $r1['ACTIVE'] == 0)
                $n = '';
            $c = $r1['CODE2'];
            if ($r1['SDDD'] == 1)
                $c = sprintf('<b>%s</b>',$r1['CODE2']);

            $fis .= sprintf('<li>[%s] %s', $c,$n);
            $fis .= OrgTree3($r1['ID']);
            $fis .= '</li>';
        }
    }
    else
    {
        $fis .= sprintf('<ul>');
        $q2 = QQ("SELECT * FROM ORGCHART WHERE PARENT = ? ORDER BY NAME ASC",array($top));
        while($r2 = $q2->fetchArray())
        {
            $n = $r2['NAME'];
            if ($r2['ACTIVE'] == 1)
                $n = sprintf('<b>%s</b>',$r2['NAME']);
            if ($mustActive && $r2['ACTIVE'] == 0)
                $n = '';
            $c = $r2['CODE2'];
            if ($r2['SDDD'] == 1)
                $c = sprintf('<b>%s</b>',$r2['CODE2']);

            $fis .= sprintf('<li>[%s] %s',$c,$n);
            $fis .= OrgTree3($r2['ID']);
            $fis .= '</li>';
        }
        $fis .= '</ul>';
    }
    $fis .= '</div>';
    return $fis;

}

function OrgTree4()
{

    $f = '<table class="table datatable"><thead><th>ID</th><th>Code</th><th>Section</th><td>SDDD</th><th>Enabled</th><th>Όνομα</th></thead><tbody>';
    QQ("BEGIN TRANSACTION");

    $q1 = QQ("SELECT * FROM ORGCHART");
    while($r1 = $q1->fetchArray())
    {
        $f .= '<tr>';
        $f .= sprintf('<td>%s</td>',$r1['ID']);
        $f .= sprintf('<td>%s</td>',$r1['ROOTCODE']);
        $f .= sprintf('<td>%s</td>',$r1['CODE2']);
        $f .= sprintf('<td>%s</td>',$r1['SDDD']);
        $f .= sprintf('<td>%s</td>',$r1['ACTIVE']);
        $f .= sprintf('<td>%s</td>',$r1['NAME']);
        $f .= '</tr>';
    }
    $f .= '</tbody></table>';
    QQ("ROLLBACK");
    return $f;
}


if (array_key_exists("table",$req))
    echo OrgTree4();
else
    echo OrgTree3(0);
