<?php

require_once "functions.php";
if (!$u)
    diez();

require_once "output.php";
if (!array_key_exists("mid",$req) || !array_key_exists("did",$req))
    diez();

$doc = DRow($req['did'],1);
$msg = MRow($req['mid'],1);

if (UserAccessDocument($req['did'],$u->uid) < 2)
    diez();
if (UserAccessMessage($req['mid'],$u->uid) < 2)
    diez();

if (array_key_exists("c",$_POST))
{
    QQ("UPDATE MESSAGES SET MSG = ?,DATE = ?,UID = ? WHERE ID = ?",array($_POST['msg'],time(),$u->uid,$_POST['mid']));
    $whereret = 'eggr.php';
    if (array_key_exists("shde_eggrurl",$_SESSION))
        $whereret = $_SESSION['shde_eggrurl'];
    redirect($whereret);
    die;
}
    
?>
<div class="content" style="margin:20px">
<form method="POST" action="text.php">
    <input type="hidden" name="c" value="1"  />
    <input type="hidden" name="mid" value="<?= $msg['ID'] ?>"  />
    <input type="hidden" name="did" value="<?= $doc['ID'] ?>"  />
    <textarea name="msg" id="msg" data-lines="100"><?= $msg['MSG']?></textarea>
    <br><br>
    <button class="button is-primary">Υποβολή</button>

</form>
<pre id="PreSave"></pre>
<script>
    $(document).ready(function()
    {
        if (typeof(Storage) !== "undefined") 
            {
                var eee = "msg" + <?= $req['mid'] ?>;
                var t = localStorage.getItem(eee);
                $("#PreSave").html(t);
            }        
        setInterval(() => {
            if (typeof(Storage) !== "undefined") 
            {
                var eee = "msg" + <?= $req['mid']  ?>;
                var t = $('#msg').val();
                localStorage.setItem(eee, t);
            }

        }, 15000);
    });
</script>


</div>