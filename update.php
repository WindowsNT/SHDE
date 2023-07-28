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
$cj = json_decode(file_get_contents("update.json"));
foreach($cj as $key=>$value)
    {
        $cv = $key;
        break;
    }
$update->setTempDir(sys_get_temp_dir());
$update->setCurrentVersion($cv);    

// Replace with your server update directory
$ur = $siteroot;
$update->setUpdateUrl($ur);

// Check for a new update
if ($update->checkUpdate() === false) {
    die('Could not check for updates! See log file for details.');
}

if ($update->newVersionAvailable()) 
{
    echo 'New Version: ' . $update->getLatestVersion() . '<br>';
    $result = $update->update();
    if ($result === true) {
        echo 'Update simulation successful<br>';
    } else {
        echo 'Update simulation failed: ' . $result . '!<br>';

        if ($result = AutoUpdate::ERROR_SIMULATE) {
            echo '<pre>';
            var_dump($update->getSimulationResults());
            echo '</pre>';
        }
    }
}
else
    echo 'No new version is available (Current:  ' . $cv . ')<br>';
