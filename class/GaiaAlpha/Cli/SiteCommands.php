<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Database;
use GaiaAlpha\Env;
use GaiaAlpha\SiteManager;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class SiteCommands
{
    public static function handleCreate()
    {
        $domain = Input::get(0);

        if (!$domain) {
            Output::writeln("Usage: php cli.php site:create <domain>");
            exit(1);
        }

        // Validate domain
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            Output::error("Invalid domain format.");
            exit(1);
        }

        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        \GaiaAlpha\File::makeDirectory($sitesDir);

        $dbPath = $sitesDir . '/' . $domain . '.sqlite';

        if (\GaiaAlpha\File::exists($dbPath)) {
            Output::error("Site '$domain' already exists.");
            exit(1);
        }

        Output::info("Creating site '$domain'...");

        // Create DB
        try {
            // Instantiate Database with new DSN
            $dsn = 'sqlite:' . $dbPath;
            $db = new Database($dsn);

            Output::writeln("Initializing schema...");
            $db->ensureSchema();

            Output::success("Site '$domain' created successfully.");
            Output::writeln("Database: $dbPath");
            Output::writeln("To manage this site, use: php cli.php --site=$domain <command>", 'cyan');

        } catch (\Exception $e) {
            Output::error("Failed to create site: " . $e->getMessage());
            if (\GaiaAlpha\File::exists($dbPath)) {
                \GaiaAlpha\File::delete($dbPath); // Cleanup
            }
            exit(1);
        }
    }

    public static function handleList()
    {
        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        $sites = [];

        // 1. Add Default
        $defaultDb = $rootDir . '/my-data/database.sqlite';
        if (\GaiaAlpha\File::exists($defaultDb)) {
            $sites[] = [
                'Domain' => '(default)',
                'Size' => number_format(filesize($defaultDb) / 1024, 2) . " KB",
                'Path' => './my-data/database.sqlite'
            ];
        }

        // 2. Add sub-sites
        if (\GaiaAlpha\File::isDirectory($sitesDir)) {
            $files = \GaiaAlpha\File::glob($sitesDir . '/*.sqlite');
            foreach ($files as $file) {
                $sites[] = [
                    'Domain' => basename($file, '.sqlite'),
                    'Size' => number_format(filesize($file) / 1024, 2) . " KB",
                    'Path' => str_replace($rootDir . '/', '', $file)
                ];
            }
        }

        if (empty($sites)) {
            Output::info("No sites found.");
            return;
        }

        Output::title("Managed Sites");
        Output::table(['Domain', 'Size', 'Path'], $sites);
    }
}
