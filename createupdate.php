<?php

die; // nwy
set_time_limit(60);
$zipFile = tempnam(sys_get_temp_dir(),"zip");
$zipFile .= ".zip";
$zipArchive = new ZipArchive();

if ($zipArchive->open($zipFile, (ZipArchive::CREATE | ZipArchive::OVERWRITE)) !== true)
    die;


$zipArchive->addGlob('./[!shde.db]*');
if ($zipArchive->status != ZIPARCHIVE::ER_OK)
    die;

$zipArchive->close();

header("Content-Type: application/zip");
header(sprintf('Content-Disposition: attachment; filename="update.zip"'));
readfile($zipFile);
unlink($zipFile);