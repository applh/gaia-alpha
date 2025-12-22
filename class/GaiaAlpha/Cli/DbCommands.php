<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;
use GaiaAlpha\File;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class DbCommands
{
    private static function runExport(string $outputFile): void
    {
        $db = \GaiaAlpha\Model\DB::connect();
        $db->dump($outputFile);
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
        File::makeDirectory($backupDir);

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

        if (!File::exists($inputFile)) {
            Output::error("Input file does not exist: $inputFile");
            exit(1);
        }

        try {
            $db = \GaiaAlpha\Model\DB::connect();
            $db->import($inputFile);
            Output::success("Database imported from $inputFile");
        } catch (\Exception $e) {
            Output::error("Database import failed: " . $e->getMessage());
            exit(1);
        }
    }

    public static function handleMigrate(): void
    {
        Output::info("Running database migrations...");
        $db = \GaiaAlpha\Model\DB::connect();
        $db->ensureSchema();
        Output::success("Migrations completed.");
    }
}
