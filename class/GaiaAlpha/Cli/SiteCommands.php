<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\File;
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
        $importPath = Input::getOption('import');

        if (!$domain) {
            Output::writeln("Usage: php cli.php site:create <domain> [--import=<path>]");
            exit(1);
        }

        // Validate domain
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            Output::error("Invalid domain format.");
            exit(1);
        }

        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        File::makeDirectory($sitesDir);

        $dbPath = $sitesDir . '/' . $domain . '.sqlite';

        if (File::exists($dbPath)) {
            Output::error("Site '$domain' already exists.");
            exit(1);
        }

        if ($importPath && !File::isDirectory($importPath)) {
            Output::error("Import path not found: $importPath");
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

            // Inject the new DB connection into global Model DB
            // This ensures all models (User, Page, etc.) use this new database
            \GaiaAlpha\Model\DB::setConnection($db);

            // Bootstrap Site
            // 1. Create Admin User
            $adminUser = 'admin';
            $adminPass = 'admin';
            $userId = \GaiaAlpha\Model\User::create($adminUser, $adminPass, 100);

            Output::success("Created default admin user: $adminUser / $adminPass");

            // 2. Create Dashboard Page
            \GaiaAlpha\Model\Page::create($userId, [
                'title' => 'App Dashboard',
                'slug' => 'app',
                'content' => '',
                'cat' => 'page',
                'template_slug' => 'app'
            ]);

            Output::success("Site '$domain' created successfully.");
            Output::writeln("Database: $dbPath");

            // Handle Import
            if ($importPath) {
                Output::writeln("Importing site package from: $importPath");

                $importer = new \GaiaAlpha\ImportExport\WebsiteImporter($importPath, $userId);
                $importer->import();

                Output::success("Site package imported successfully.");
            }

            Output::writeln("To manage this site, use: php cli.php --site=$domain <command>", 'cyan');
            Output::writeln("Login at: http://$domain:8000/app", 'cyan');

        } catch (\Exception $e) {
            Output::error("Failed to create site: " . $e->getMessage());
            if (File::exists($dbPath)) {
                // File::delete($dbPath); // Cleanup? Maybe keep for debugging if partial failure
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
        if (File::exists($defaultDb)) {
            $sites[] = [
                'Domain' => '(default)',
                'Size' => number_format(filesize($defaultDb) / 1024, 2) . " KB",
                'Path' => './my-data/database.sqlite'
            ];
        }

        // 2. Add sub-sites
        if (File::isDirectory($sitesDir)) {
            $files = File::glob($sitesDir . '/*.sqlite');
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

    public static function handleDelete()
    {
        $domain = Input::get(0);

        if (!$domain) {
            Output::writeln("Usage: php cli.php site:delete <domain>");
            exit(1);
        }

        $rootDir = Env::get('root_dir');
        $dbPath = $rootDir . '/my-data/sites/' . $domain . '.sqlite';

        if (!File::exists($dbPath)) {
            Output::error("Site '$domain' not found.");
            exit(1);
        }

        Output::warning("You are about to DELETE the site '$domain'.");
        Output::warning("Database: $dbPath");
        Output::writeln("This action cannot be undone. All data will be lost.", 'red');

        echo "Are you sure? [y/N] ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) != 'y') {
            Output::writeln("Aborted.");
            return;
        }
        fclose($handle);

        if (File::delete($dbPath)) {
            Output::success("Site '$domain' deleted successfully.");
        } else {
            Output::error("Failed to delete site database.");
        }
    }
}
