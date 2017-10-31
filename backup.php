<?php

// Autoload
require __DIR__ . '/vendor/autoload.php';

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

date_default_timezone_set('Europe/Berlin');

$type = '';
if (defined('STDIN')) {
  $type = $argv[1].'.';
}
$settings = require 'config.'.$type.'php';

$folder = '';
$prefix = 'backup_'.$settings['mysql']['database'].'_';

$sqlFileName    = $prefix.date('Y_m_d_H-i-s').".sql";
$sqlFile        = $folder.$sqlFileName;
$zipFileName    = $prefix.date('Y_m_d_H-i-s').".zip";
$zipFile        = $folder.$zipFileName;

$createSQLBackup   = "mysqldump -h ".$settings['mysql']['host']." -u ".$settings['mysql']['user']." --password='".$settings['mysql']['password']."' ".$settings['mysql']['database']." > ".$sqlFile." 2>&1";
$createZipBackup = "zip -P ".$settings['zip_password']." $zipFile $sqlFile";

try {
    exec($createSQLBackup);
    system($createZipBackup);
} catch (Exception $e) {
    echo "Failed to create Backup or ZIP: " . $e->getMessage() . "\n";
}

//Configure Dropbox Application
$app = new DropboxApp($settings['dropbox']['client_id'], $settings['dropbox']['client_secret'], $settings['dropbox']['access_token']);

//Configure Dropbox service
$dropbox = new Dropbox($app);

try {
    // Create Dropbox File from Path
    $dropboxFile = new DropboxFile($zipFile);

    // Upload the file to Dropbox
    $uploadedFile = $dropbox->upload($dropboxFile, "/" . $zipFileName, ['autorename' => true]);

    //echo $uploadedFile->getPathDisplay();
    
} catch (DropboxClientException $e) {
    echo $e->getMessage();
}


// Delete the temporary files
try {
    unset($dropboxFile);
    unlink($sqlFile);
    unlink($zipFile);
} catch (Exception $e) {
    echo "Failed to unlink Backup temporary file: " . $e->getMessage() . "\n";
}
