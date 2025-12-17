<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;
use GaiaAlpha\Cli\DbCommands;
use GaiaAlpha\Cli\Output;

class SaveCommands
{
    public static function handleAll(): void
    {
        Output::title("Full System Backup");

        Output::info("Step 1: Saving database...");
        DbCommands::handleSave();

        Output::info("Step 2: Zipping my-data folder...");

        $rootDir = Env::get('root_dir');
        $sourceDir = 'my-data'; // Relative to root
        $timestamp = date('Y-m-d_H-i-s');

        // Ensure root-level backups directory exists
        $backupDir = $rootDir . '/backups';
        \GaiaAlpha\File::makeDirectory($backupDir);

        $zipFile = $backupDir . '/my-data_' . $timestamp . '.zip';

        // Zip command
        $cmd = "cd " . escapeshellarg($rootDir) . " && zip -r -q " . escapeshellarg($zipFile) . " " . escapeshellarg($sourceDir);

        Output::writeln("Creating zip archive at $zipFile ...");
        passthru($cmd, $returnVar);

        if ($returnVar !== 0) {
            Output::error("Failed to zip my-data folder.");
            exit(1);
        }

        Output::success("Successfully saved my-data to $zipFile");
    }
}
