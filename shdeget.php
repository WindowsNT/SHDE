<?php

require_once "functions.php";
if (!$u)
    diez();

require_once "output.php";

$whereret = 'eggr.php';
if (array_key_exists("shde_eggrurl",$_SESSION))
    $whereret = $_SESSION['shde_eggrurl'];

