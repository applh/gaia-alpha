<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;
use GaiaAlpha\Cli\DbCommands;

class SaveCommands
{
    public static function handleAll(): void
    {
        echo "Step 1: Saving database...\n";
        DbCommands::handleSave();

        echo "Step 2: Zipping my-data folder...\n";

        $rootDir = Env::get('root_dir');
        $sourceDir = 'my-data'; // Relative to root
        $timestamp = date('Y-m-d_H-i-s');

        // Ensure root-level backups directory exists
        $backupDir = $rootDir . '/backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $zipFile = $backupDir . '/my-data_' . $timestamp . '.zip';

        // Zip command
        // -r: recursive
        // -q: quiet
        // We cd to root first to zip 'my-data' as a folder
        $cmd = "cd " . escapeshellarg($rootDir) . " && zip -r -q " . escapeshellarg($zipFile) . " " . escapeshellarg($sourceDir);

        echo "Creating zip archive at $zipFile ...\n";
        passthru($cmd, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to zip my-data folder.\n";
            exit(1);
        }

        echo "Successfully saved my-data to $zipFile\n";
    }
}
