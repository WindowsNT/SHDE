<?php

require_once "functions.php";
require_once "output.php";
if (!$u)
    diez();

if (!array_key_exists("eid",$req))
    $req['eid'] = 0;
if (!array_key_exists("oid",$req))
    $req['oid'] = 0;

if (array_key_exists("del",$req))
{
    $r = QQ("SELECT * FROM RULES WHERE ID = ?",array($req['del']))->fetchArray();
    if (!$r)
        die;
    $a = 0;
    if ($r['EID'])    
        $a = UserAccessEP($r['EID'],$u->uid);
    else
    if ($r['OID'])
        $a = UserAccessOID($r['OID'],$u->uid);
    if ($a == 2)
        QQ("DELETE FROM RULES WHERE ID = ?",array($req['del']));
    redirect("rules.php");
    die;
}

if (array_key_exists("createrule",$req))
{
    $arr = array(
        $req['oid'],$req['eid'],$req['title'],$req['conditions'],$req['actions'],$req['andor']
    );
    if ($req['createrule'] == 0)
        QQ("INSERT INTO RULES (OID,EID,TITLE,CONDITIONS,ACTIONS,ANDOR) VALUES(?,?,?,?,?,?)",$arr);
        else
        {
            $arr[] = $req['createrule'];
            QQ("UPDATE RULES SET OID = ?,EID = ?,TITLE = ?,CONDITIONS = ?,ACTIONS = ?,ANDOR = ? WHERE ID = ?",$arr);
        }
    redirect("rules.php");
    die;
}

if (array_key_exists("new",$req))
{
    echo '<div class="content" style="margin: 20px">';
    $rr = QQ("SELECT * FROM RULES WHERE ID = ?",array($req['new']))->fetchArray();
    if (!$rr)
        $rr = array("ID" => 0,"EID" => $req['eid'],"OID" => $req['oid'],"TITLE" => "", "ANDOR" => 1, "CONDITIONS" => '{
            "topic" : "/.*/",
            "frommail" : "/.*/",
            "fromname" : "/.*/"
}',"ACTIONS" => '{
    "movefolder" : 0
}');

    ?>
        <form method="POST" action="rules.php">
            <input type="hidden" name="createrule" value="<?= $rr['ID'] ?>">
                <div class="columns">
                    <?php
                        echo '<div class="column">';
                        printf('Φορέας:<br> %s<br><br>',PickOrganization("oid",array($rr['OID']),0,$u->uid,0,0,2));
                        echo '</div><div class="column">';
                        printf('Endpoint:<br> %s<br>', PickEP("eid",array($rr['EID']),0,$u->uid,0,0,2,2));
                        echo '</div>';
                    ?>
                </div>
                Tίτλος κανόνα:
                <input type="text" class="input" name="title" value="<?= $rr['TITLE']?>" required/>
                <br><br>

                JSon regex συνθηκών:
                <textarea class="textarea" name="conditions" required><?= $rr['CONDITIONS']?></textarea>
                <br><br>

                JSon ενεργειών:
                <textarea class="textarea" name="actions" required><?= $rr['ACTIONS']?></textarea>
                <br><br>

                And/Or:
                <select name="andor" class="input">
                    <option value="0" <?= $rr['ANDOR'] == 0 ? "selected" : ""?>>OR</option>
                    <option value="1" <?= $rr['ANDOR'] == 1 ? "selected" : ""?>>AND</option>
                </select>

                <br><br>
                <button class="button is-primary">Υποβολή</button>
        </form>
            <button class="button is-danger autobutton" href="rules.php">Άκυρο</button>
    <?php
    echo '</div>';
    die;
}

if ($u->superadmin || count($u->fadmin))
    PrintHeader('endpoints.php',sprintf('&nbsp; <button class="button is-primary block autobutton" href="rules.php?new=0&eid=%s&oid=%s">Νέο Rule</button>',$req['eid'],$req['oid']));
else
    PrintHeader('endpoints.php');

?>

    <script>
        function del(id)
        {
            if (!confirm("Σίγουρα;"))
                return;
            window.location = 'rules.php?del=' + id;
        }
    </script>
    <table class="table datatable">
        <thead>
            <th>ID</th>
            <th>Φορέας</th>
            <th>Endpoint</th>
            <th>Τίτλος</th>
            <th>Συνθήκες</th>
            <th>Ενέργειες</th>
            <th>AND/OR</th>
            <th></th>
        </thead>
        <tbody>
<?php
$q = QQ("SELECT * FROM RULES");
while($r = $q->fetchArray())
{
    $fr = FRow($r['OID']);
    $er = EPRow($r['EID']);
    $a = 0;
    if ($r['EID'])    
        $a = UserAccessEP($r['EID'],$u->uid);
    else
    if ($r['OID'])
        $a = UserAccessOID($r['OID'],$u->uid);
    if ($a != 2)
        continue;
    printf('<tr>');
    printf('<td>%s</td>',$r['ID']);
    printf('<td>%s</td>',$fr ? $fr['NAME'] : '');
    printf('<td>%s</td>',$er ? $er['NAME'] : '');
    printf('<td>%s</td>',$r['TITLE']);
    printf('<td><xmp>%s</xmp></td>',$r['CONDITIONS']);
    printf('<td><xmp>%s</xmp></td>',$r['ACTIONS']);
    printf('<td>%s</td>',$r['ANDOR'] == 0 ? "OR" : "AND");
    printf('<td><a href="rules.php?new=%s">Επεξεργασία</a> &mdash; <a href="javascript:del(%s);">Διαγραφή</a></td>',$r['ID'],$r['ID']);
    printf('</tr>');
}
echo '</tbody></table>';
