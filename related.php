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
$acc = UserAccessDocument($req['did'],$u->uid);
if ($acc != 2)
    {
        redirect($whereret);
        die;
    }

if (array_key_exists("rels",$_POST) && $acc == 2)
{
    $dr0 = DRow($req['did'],1);
    $rels = explode(",",$req['rels']);
    foreach($rels as $rel)
    {
        $dr = DRow($rel,1);
        if (UserAccessDocument($rel,$u->uid) == 0)
            die("Cannot access document $rel");
        if ($rel == $req['did'])
            die("Cannot access document $rel");
        if ($dr['CLASSIFIED'] > $dr0['CLASSIFIED'])
            die("Classification error $rel");
    }
    QQ("UPDATE DOCUMENTS SET RELATED = ? WHERE ID = ?",array(implode(",",$rels),$req['did']));
    redirect($whereret);
    die;
}
if (array_key_exists("ENCRYPTED",$doc) && $doc['ENCRYPTED'] == 1)
    {
        redirect(sprintf("decrypt.php?did=%s",$doc['ID']));
        die;
    }

PrintHeader($whereret);
?>
Σχετικά έγγραφα (Δώστε τα ID των σχετικών, χωρισμένα με κόμμα):<hr>
<form method="POST" action="related.php">
<input type="hidden" name="did" value="<?= $req['did'] ?>">
<input class="input" name="rels" value="<?= $doc['RELATED'] ?>" >
<br><br>
<button class="button is-primary">Υποβολή</button>
</form>
<?php

?>