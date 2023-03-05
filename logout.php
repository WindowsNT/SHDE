<?php

$in_bio = 1;
require_once "functions.php";
$ret = $req['return'];

unset($_SESSION['shde_needbio']);
unset($_SESSION['shde_bio']);
unset($_SESSION['shde_username']);
unset($_SESSION['shde_lastname']);
unset($_SESSION['shde_firstname']);
unset($_SESSION['shde_title']);
session_destroy();
redirect($ret);

