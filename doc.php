<?php

require_once "functions.php";
if (!$u)
    diez();
require_once "output.php";
$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

$doc = DRow($req['did'],1);
if (!$doc)
    diez();
$a = UserAccessDocument($doc['ID'],$u->uid);
if ($a == 0)
    diez();

PrintHeader();
printf('<button href="%s" class="autobutton button is-danger is-small">Πίσω</button><br><br>',$whereret);
$eidr = EPRow($doc['EID']);
$fr = FRow($eidr['OID']);
printf ("Έγγραφο με θέμα: <b>%s</b><br>",$doc['TOPIC']);
printf ("Οργανισμός: %s &mdash; %s<br>",$fr['NAME'],$eidr['NAME']);
if ($doc['PROT'] == NULL || !strlen($doc['PROT']))
{
}
else
{
    $pr = (unserialize($doc['PROT']));
    printf("Πρωτόκολλο: <b>%s</b> &mdash; %s<br>",$pr['n'],date("d/m/Y H:i",$pr['t']));
}
printf("Κατηγορία: <b>%s</b><br>",CategoryFromID($doc['CATEGORY']));
printf("Διαβάθμιση: <b>%s</b><br>",ClassificationString($doc['CLASSIFIED'],0));
if ($doc['DUEDATE'] && strlen($doc['DUEDATE']))
    printf("Διεκπεπαίρωση μέχρι: %s<br>",date("d/m/Y",$doc['DUEDATE']));
printf('<a href="recp.php?did=%s">Παραλήπτες</a><br>',$doc['ID']);

printf ('<br><br>Εκδόσεις<hr>');
$q2 = QQ("SELECT * FROM MESSAGES WHERE DID = ? ORDER BY DATE DESC",array($doc['ID']));
while($r2 = $q2->fetchArray())
{
    DocumentDecrypt($r2);
    printf ('<a href="print.php?did=%s&mid=%s&pdf=1" target="_blank"><b>Έκδοση %s</b></a><br>',$doc['ID'],$r2['ID'],date("j/m/Y",$r2['DATE']));
    printf ($r2['MSG']);
    printf('<a href="att.php?did=%s&mid=%s">Επισυναπτόμενα</a><br>',$doc['ID'],$r2['ID']);

    $q3 = QQ("SELECT * FROM COMMENTS WHERE DID = ? AND MID = ?",array($doc['ID'],$r2['ID']));
    $nc = 0;
    while($r3 = $q3->fetchArray())
    {
        DocumentDecrypt($r3);
        if ($nc == 0)
            printf('<br>[<i><br>');
        $ur = UserRow($r3['UID']);
        printf("%s %s [%s]<br>%s",$ur['LASTNAME'],$ur['FIRSTNAME'],date("d/m/Y H:i",$r3['DATE']),$r3['COMMENT']);
        $nc++;
    }
    if ($nc)
        printf('</i>]');
    echo '<hr>';
}