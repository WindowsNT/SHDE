<?php

require_once "./vendor/autoload.php";
use \VisualAppeal\AutoUpdate;

require_once "functions.php";
if (!$u)
    diez();
if (!$u->superadmin)
    diez();
require_once "output.php";
PrintHeader('index.php');

$update = new AutoUpdate(__DIR__ . '/temp', __DIR__ . '/../', 60);
$update->setCurrentVersion('0.0.0');    


// Replace with your server update directory
$update->setUpdateUrl('https://www.msa-apps.com/shde/update.json');

// Check for a new update
if ($update->checkUpdate() === false) {
    die('Could not check for updates! See log file for details.');
}

if ($update->newVersionAvailable()) 
{

}
else
    die("<br><br>No new version is available.");
