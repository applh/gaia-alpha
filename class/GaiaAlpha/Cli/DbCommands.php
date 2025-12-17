<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class DbCommands
{
    private static function runExport(string $outputFile): void
    {
        if (!defined('GAIA_DB_PATH')) {
            Output::error("GAIA_DB_PATH is not defined.");
            exit(1);
        }

        $dbPath = GAIA_DB_PATH;
        if (!\GaiaAlpha\File::exists($dbPath)) {
            Output::error("Database file does not exist at $dbPath");
            exit(1);
        }

        $cmd = "sqlite3 " . escapeshellarg($dbPath) . " .dump";
        $cmd .= " > " . escapeshellarg($outputFile);

        passthru($cmd, $returnVar);

        if ($returnVar !== 0) {
            Output::error("Database export failed.");
            exit(1);
        }

        Output::success("Database exported to $outputFile");
    }

    public static function handleExport(): void
    {
        $outputFile = Input::get(0);

        if (!$outputFile) {
            Output::writeln("Usage: php cli.php db:export <file.sql>");
            exit(1);
        }

        self::runExport($outputFile);
    }

    public static function handleSave(): void
    {
        $backupDir = Env::get('root_dir') . '/my-data/backups';
        \GaiaAlpha\File::makeDirectory($backupDir);

        $timestamp = date('Y-m-d_H-i-s');
        $outputFile = $backupDir . '/db_' . $timestamp . '.sql';

        self::runExport($outputFile);
    }

    public static function handleImport(): void
    {
        $inputFile = Input::get(0);

        if (!$inputFile) {
            Output::writeln("Usage: php cli.php db:import <file.sql>");
            exit(1);
        }

        if (!\GaiaAlpha\File::exists($inputFile)) {
            Output::error("Input file does not exist: $inputFile");
            exit(1);
        }

        if (!defined('GAIA_DB_PATH')) {
            Output::error("GAIA_DB_PATH is not defined.");
            exit(1);
        }

        $dbPath = GAIA_DB_PATH;

        $cmd = "sqlite3 " . escapeshellarg($dbPath) . " < " . escapeshellarg($inputFile);

        passthru($cmd, $returnVar);

        if ($returnVar !== 0) {
            Output::error("Database import failed.");
            exit(1);
        }

        Output::success("Database imported from $inputFile");
    }

    public static function handleMigrate(): void
    {
        Output::info("Running database migrations...");
        $db = \GaiaAlpha\Model\DB::connect();
        $db->ensureSchema();
        Output::success("Migrations completed.");
    }
}
