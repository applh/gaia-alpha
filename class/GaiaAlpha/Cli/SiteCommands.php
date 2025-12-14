<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Database;
use GaiaAlpha\Env;
use GaiaAlpha\SiteManager;

class SiteCommands
{
    public static function handleCreate()
    {
        global $argv;
        $domain = $argv[2] ?? null;

        if (!$domain) {
            echo "Usage: php cli.php site:create <domain>\n";
            exit(1);
        }

        // Validate domain
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            echo "Error: Invalid domain format.\n";
            exit(1);
        }

        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        if (!is_dir($sitesDir)) {
            mkdir($sitesDir, 0755, true);
        }

        $dbPath = $sitesDir . '/' . $domain . '.sqlite';

        if (file_exists($dbPath)) {
            echo "Error: Site '$domain' already exists.\n";
            exit(1);
        }

        echo "Creating site '$domain'...\n";

        // Create DB
        try {
            // Instantiate Database with new DSN
            $dsn = 'sqlite:' . $dbPath;
            $db = new Database($dsn);

            echo "Initializing schema...\n";
            $db->ensureSchema();

            // Should we seed it?
            // Maybe just default user?
            // Let's invoke UserCommands::handleCreate if we can, or just insert a default user manually?
            // For now, empty Schema is good.

            echo "Site '$domain' created successfully.\n";
            echo "Database: $dbPath\n";
            echo "To manage this site, use: php cli.php --site=$domain <command>\n";

        } catch (\Exception $e) {
            echo "Error creating site: " . $e->getMessage() . "\n";
            if (file_exists($dbPath)) {
                unlink($dbPath); // Cleanup
            }
            exit(1);
        }
    }

    public static function handleList()
    {
        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        if (!is_dir($sitesDir)) {
            echo "No sites directory found.\n";
            return;
        }

        $files = glob($sitesDir . '/*.sqlite');

        echo "Managed Sites:\n";
        if (empty($files)) {
            echo "  (No sites found)\n";
        } else {
            foreach ($files as $file) {
                $domain = basename($file, '.sqlite');
                $size = filesize($file);
                echo "  - $domain (" . number_format($size / 1024, 2) . " KB)\n";
            }
        }

        // Also mention default?
        $defaultDb = $rootDir . '/my-data/database.sqlite';
        if (file_exists($defaultDb)) {
            echo "  - (default) (" . number_format(filesize($defaultDb) / 1024, 2) . " KB)\n";
        }
    }
}
