<?php

require __DIR__ . '/vendor/autoload.php';

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

date_default_timezone_set('Europe/Berlin');

$type = '';
if (defined('STDIN') && count($argv) > 1) {
    $type = $argv[1] . '.';
}
$settings = require 'config.' . $type . 'php';

$folder = $settings['folder'];
$prefix = $settings['prefix'];
$suffix = $settings['suffix'];

$app = new DropboxApp($settings['dropbox']['client_id'], $settings['dropbox']['client_secret'], $settings['dropbox']['access_token']);
$dropbox = new Dropbox($app);

$fileExtension = ".zip";
if (`which 7z`) {
    $fileExtension = ".7z";
}

/**
 * Create SQL Backup and zip
 */
if (array_key_exists('mysql', $settings)) {
    $fileSQLName = $folder . $prefix . date('Y_m_d_H-i-s') . $suffix . ".sql";
    $fileZipSQLName = $prefix . date('Y_m_d_H-i-s') . $suffix . $fileExtension;
    $fileZipSQLPath = $folder . $fileZipSQLName;

    $createSQLBackup = "mysqldump -h " . $settings['mysql']['host'] . " -u " . $settings['mysql']['user'] . " --password='" . $settings['mysql']['password'] . "' " . $settings['mysql']['database'] . " > " . $fileSQLName . " 2>&1";
    if (`which 7z`) {
        $createZipSQLBackup = "7z a -t7z -p" . $settings['zip_password'] . " -mhe -r -spf2 $fileZipSQLPath $fileSQLName";
    } else {
        $createZipSQLBackup = "zip -P " . $settings['zip_password'] . " $fileZipSQLPath $fileSQLName";
    }

    try {
        exec($createSQLBackup);
        system($createZipSQLBackup);
    } catch (Exception $e) {
        echo "Failed to create Backup or ZIP: " . $e->getMessage() . "\n";
    }

    /**
     * Upload SQL Zip
     */
    try {
        $fileZipSQLDropbox = new DropboxFile($fileZipSQLPath);
        $fileZipSQLDropboxUploaded = $dropbox->upload($fileZipSQLDropbox, "/" . $fileZipSQLName, ['autorename' => true]);

        //echo $fileZipSQLDropboxUploaded->getPathDisplay();
    } catch (DropboxClientException $e) {
        echo $e->getMessage();
    }


    /**
     * Delete the temporary files
     */
    try {
        unset($fileZipSQLDropbox);
        unlink($fileSQLName);
        unlink($fileZipSQLPath);
    } catch (Exception $e) {
        echo "Failed to unlink Backup temporary file: " . $e->getMessage() . "\n";
    }

}

/**
 * Create other files zip
 */
if (array_key_exists('files', $settings) && !empty($settings['files'])) {
    $fileZipOtherFilesName = $prefix . date('Y_m_d_H-i-s') . $suffix . $fileExtension;
    $fileZipOtherFilesPath = $folder . $fileZipOtherFilesName;

    if(`which 7z`){
        $createZipOtherFilesBackup = "7z a -t7z -p" . $settings['zip_password'] . " -mhe -r -spf2 $fileZipOtherFilesPath " . implode(" ", $settings['files']);
    } else {
        $createZipOtherFilesBackup = "zip -P " . $settings['zip_password'] . " -r $fileZipOtherFilesPath " . implode(" ", $settings['files']);
    }

    try {
        system($createZipOtherFilesBackup);
    } catch (Exception $e) {
        echo "Failed to create ZIP of folders: " . $e->getMessage() . "\n";
    }

    /**
     * Upload other files zip
     */
    try {
        $fileZipOtherFilesDropbox = new DropboxFile($fileZipOtherFilesPath);
        $fileZipOtherFilesDropboxUploaded = $dropbox->upload($fileZipOtherFilesDropbox, "/" . $fileZipOtherFilesName, ['autorename' => true]);
    } catch (DropboxClientException $e) {
        echo $e->getMessage();
    }

    /**
     * Delete the temporary files
     */
    try {
        unset($fileZipOtherFilesDropbox);
        unlink($fileZipOtherFilesPath);
    } catch (Exception $e) {
        echo "Failed to unlink Backup temporary file: " . $e->getMessage() . "\n";
    }
}
