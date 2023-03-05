<?php

require_once "functions.php";
if (!$u)
    diez();

$ur = UserRow($u->uid);

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

if ($ur['CLASSIFIED'] == 0) 
{
    redirect($whereret);
    die;
}

if (array_key_exists("didx",$_POST))
{
    $_SESSION[sprintf('shde_pwd_%s',$_POST['didx'])] = $_POST['pwd'] ;
    redirect($whereret);
    die;
}
    
if (array_key_exists("did",$_GET))
{
    require_once "output.php";
    ?>
    <div class="content" style="margin:20px">
    <form method="POST" action="decrypt.php" >

    Κωδικός:<br><br>
    <input class="input" type="password" name="pwd" value="" autocomplete="one-time-code" autofocus/>
    <input type="hidden" name="didx" value="<?= $_GET['did'] ?>" />
    <br><br><hr>
    <button class="button is-primary">Υποβολή</button>
    </form>
    <?php 
        printf('<button href="%s" class="autobutton button is-danger">Άκυρο</button>',$whereret);
    ?>

    <?php
}  
?>

