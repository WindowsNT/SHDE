<?php


require_once "functions.php";
if (!$u)
    diez();
set_time_limit(120);
require_once "output.php";
$parag = 0;

if ($u->superadmin)
    PrintHeader('index.php','&nbsp; <button class="button is-success autobutton block" href="orgchart.php?table=1">Μορφή Πίνακα</button> &nbsp; <button class="button is-link autobutton block" href="orgchart.php?reload=1">Ανανέωση</button><span></span>');
else
    PrintHeader('index.php','&nbsp; <button class="button is-small is-success autobutton block" href="orgchart.php?table=1">Μορφή Πίνακα</button> ');


if (1)
{
    $q1 = QQ("SELECT * FROM ORGANIZATIONS");
    while($r1 = $q1->fetchArray())
    {
        $parag = 1;
        if ($r1['SHDEPRODUCTION'] == 0)
            {
                $parag = 0;
                printf("Δοκιμαστικό Οργανόγραμμα<br>");
            }
        else
        {
            printf("Παραγωγικό Οργανόγραμμα<br>");
        }
    }
}

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
   global $parag; 
    global $lastRowID;
    if ($j->IsSDDDNode)
        $root = $j->Code;
    else
        $root = $rootx;
    $rootx = $root;
    $finalcode = $j->Code;
    if ($finalcode != $root)
        $finalcode = sprintf("%s|%s",$root,$j->Code);
    QQ("INSERT INTO ORGCHART (CODE,NAME,NAMEEN,ACTIVE,SDDD,PARENT,CODE2,ROOTCODE,PARAG) VALUES(?,?,?,?,?,?,?,?,?)",array(
        $finalcode,$j->Name,$j->NameEnglish,$j->IsActive,$j->IsSDDDNode,$par,$j->Code,$root,$parag
    ));
    $id = $lastRowID;
    foreach($j->ChildNodes as $dep)
        AddDep($dep,$id,0,$rootx);
    foreach($j->Departments as $ch)
        AddDep($ch,$id,1,$rootx);
}


$allmap = array();
function LoadAll()
{
    global $allmap,$parag;
    QQ("BEGIN TRANSACTION");
    $q1 = QQ("SELECT * FROM ORGCHART WHERE PARAG = ?",array($parag));
    while($r1 = $q1->fetchArray())
        $allmap[] = $r1;
    QQ("ROLLBACK");
}

$allmap2 = array();
function LoadAll2($parent = 0,array& $what = null)
{
    global $allmap;
    foreach($allmap as $row)
    {
        if ((int)$parent == (int)$row['PARENT'])
        {
            $items = array();
            LoadAll2($row['ID'],$items);
            $row["items"] = $items;
            $what[$row['ID']] = $row;
        }
    }
}

function ReceiveOrgLive()
{
    global $siteroot,$parag;
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
    //    printr($j); die; 
    QQ("DELETE FROM ORGCHART WHERE PARAG = ?",array($parag));
    QQ("BEGIN TRANSACTION");
    QQ("UPDATE ORGCHART SET ACTIVE = 0 WHERE PARAG = ?",array($parag)); 
    foreach($j->RootNode->ChildNodes as $ch)
        AddDep($ch,0,0,0);
    QQ("COMMIT");

}

if (array_key_exists("reload",$req) && $u->superadmin)
    {
        ReceiveOrgLive(0);
        QQ("BEGIN TRANSACTION;");
        $q1 = QQ("SELECT * FROM ORGCHART WHERE PARAG = ?",array($parag));
        while($r1 = $q1->fetchArray())
        {
            $fn = OrgChartFullName2($r1['ID']);
            QQ("UPDATE ORGCHART SET FULLNAME = ? WHERE ID = ?",array(implode("&mdash;",array_reverse($fn)),$r1['ID']));
        }
        QQ("COMMIT");


        // And serialize
        global $allmap2;
        LoadAll();
        LoadAll2(0,$allmap2);
        $s = serialize($allmap2);
        QQ("DELETE FROM ORGCHARTCACHE WHERE MODE = 1");
        QQ("INSERT INTO ORGCHARTCACHE (MODE,DATA) VALUES(?,?)",array(1,$s));
    

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
        $q1 = QQ("SELECT * FROM ORGCHART WHERE PARENT = 0 AND PARAG = ? ORDER BY NAME ASC",array($parag));
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
        $q2 = QQ("SELECT * FROM ORGCHART WHERE PARENT = ? AND PARAG = ? ORDER BY NAME ASC",array($top,$parag));
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


/*
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
        QQ("BEGIN TRANSACTION");
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
    if ($top == 0)
        QQ("ROLLBACK");
    return $fis;
}
*/
/*
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

*/
//die;

/*if (array_key_exists("table",$req))
    echo OrgTree4();
else
    echo OrgTree3(0);
*/

$actives = 0;
$subactives = 0;
function OrgTree5(array& $top)
{
    global $actives,$subactives;
    global $req;
    echo '<style>
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

    echo sprintf('<ul>');
    foreach($top as $r1)
    {
        if ($r1['ACTIVE'] == 1 && $r1['SDDD'] == 1)
            $actives++;

        $n = $r1['NAME'].' '.$r1['NAMEEN'];
        if ($r1['ACTIVE'] == 1)
            $n = sprintf('<b>%s <i>%s</i></b>',$r1['NAME'],$r1['NAMEEN']);
        if ($mustActive && $r1['ACTIVE'] == 0)
            $n = '';
        $c = $r1['CODE2'];
        if ($r1['SDDD'] == 1)
            $c = sprintf('<b>%s</b>',$r1['CODE2']);

        echo sprintf('<li>[%s] %s', $c,$n);
        echo OrgTree5($r1['items']);
        echo  '</li>';
    }
    echo sprintf('</ul>');
    echo '</div>';
}

$chart = LoadChart();
OrgTree5($chart);
printf("Ενεργοί οργανισμοί: %d",$actives);