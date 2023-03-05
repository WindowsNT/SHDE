<?php

require_once "functions.php";
if (!$u)
    diez();
if (!$u->superadmin)
    diez();

if (array_key_exists("uid",$_POST))
{
    $ur = UserRow($_POST['uid']);
    session_destroy();
    session_start();
    $_SESSION['shde_username'] = $ur['USERNAME'];
    $_SESSION['shde_firstname'] = $ur['FIRSTNAME'];
    $_SESSION['shde_lastname'] = $ur['LASTNAME'];
    $_SESSION['shde_title'] = $ur['TITLE'];
    redirect("index.php");
    die;
}

require_once "output.php";
PrintHeader('index.php');

?>

<form method="POST" action="impersonate.php">

<?php


echo  PickUser("uid",array(),0,array()); 

?>

<br><br>
        <button class="button is-primary">Υποβολή</button>
        </form>


