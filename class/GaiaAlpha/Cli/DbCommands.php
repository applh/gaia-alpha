<?php

namespace GaiaAlpha\Cli;

use Exception;
use GaiaAlpha\Env;

class DbCommands
{
    public static function handleExport(): void
    {
        global $argv;
        $outputFile = $argv[2] ?? null;

        if (!defined('GAIA_DB_PATH')) {
            echo "Error: GAIA_DB_PATH is not defined.\n";
            exit(1);
        }

        $dbPath = GAIA_DB_PATH;
        if (!file_exists($dbPath)) {
            echo "Error: Database file does not exist at $dbPath\n";
            exit(1);
        }

        $cmd = "sqlite3 " . escapeshellarg($dbPath) . " .dump";

        if ($outputFile) {
            $cmd .= " > " . escapeshellarg($outputFile);
        }

        passthru($cmd, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Database export failed.\n";
            exit(1);
        }

        if ($outputFile) {
            echo "Database exported to $outputFile\n";
        }
    }

    public static function handleImport(): void
    {
        global $argv;
        $inputFile = $argv[2] ?? null;

        if (!$inputFile) {
            echo "Usage: php cli.php db:import <file.sql>\n";
            exit(1);
        }

        if (!file_exists($inputFile)) {
            echo "Error: Input file does not exist: $inputFile\n";
            exit(1);
        }

        if (!defined('GAIA_DB_PATH')) {
            echo "Error: GAIA_DB_PATH is not defined.\n";
            exit(1);
        }

        $dbPath = GAIA_DB_PATH;
        // Verify we can write to the directory appropriately or file exists
        // sqlite3 will create it if not exists, but let's check basic permissions conceptually?
        // Actually, just let sqlite3 handle it.

        $cmd = "sqlite3 " . escapeshellarg($dbPath) . " < " . escapeshellarg($inputFile);

        passthru($cmd, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Database import failed.\n";
            exit(1);
        }

        echo "Database imported from $inputFile\n";
    }
}
