<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\File;
use GaiaAlpha\Database;
use GaiaAlpha\Env;
use GaiaAlpha\SiteManager;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;
use GaiaAlpha\Model\DataStore;

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
        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';
        $sitePath = $sitesDir . '/' . $domain;

        if (File::isDirectory($sitePath)) {
            Output::error("Site directory '$domain' already exists.");
            exit(1);
        }

        File::makeDirectory($sitePath);
        File::makeDirectory($sitePath . '/assets');

        $dbPath = $sitePath . '/database.sqlite';

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

                $importer = new \GaiaAlpha\ImportExport\WebsiteImporter($importPath, $userId, $sitePath . '/assets');
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
            $dirs = File::glob($sitesDir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                // Check if database exists inside
                if (File::exists($dir . '/database.sqlite')) {
                    $sites[] = [
                        'Domain' => basename($dir),
                        'Size' => number_format(filesize($dir . '/database.sqlite') / 1024, 2) . " KB",
                        'Path' => str_replace($rootDir . '/', '', $dir . '/database.sqlite')
                    ];
                }
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
        $sitePath = $rootDir . '/my-data/sites/' . $domain;

        if (!File::isDirectory($sitePath)) {
            Output::error("Site '$domain' not found.");
            exit(1);
        }

        Output::warning("You are about to DELETE the site '$domain'.");
        Output::warning("Directory: $sitePath");
        Output::writeln("This action cannot be undone. All data will be lost.", 'red');

        echo "Are you sure? [y/N] ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) != 'y') {
            Output::writeln("Aborted.");
            return;
        }
        fclose($handle);

        // Recursive delete
        if (self::deleteDir($sitePath)) {
            Output::success("Site '$domain' deleted successfully.");
        } else {
            Output::error("Failed to delete site directory.");
        }
    }

    private static function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new \InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        return rmdir($dirPath);
    }

    public static function handleVerifyExport()
    {
        $packagePath = Input::getOption('package');
        if (!$packagePath) {
            $packagePath = Env::get('root_dir') . '/docs/examples/enterprise_site';
        }

        if (!File::isDirectory($packagePath)) {
            Output::error("Package not found: $packagePath");
            exit(1);
        }

        Output::title("Verifying Site Export");
        Output::info("Package: $packagePath");

        // 1. Setup Temporary Site
        $domain = 'verify_temp_' . time();
        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';
        $sitePath = $sitesDir . '/' . $domain;
        File::makeDirectory($sitePath);
        File::makeDirectory($sitePath . '/assets');
        $dbPath = $sitePath . '/database.sqlite';

        try {
            // Create DB
            $dsn = 'sqlite:' . $dbPath;
            $db = new \GaiaAlpha\Database($dsn);
            $db->ensureSchema();

            // Set Connection
            \GaiaAlpha\Model\DB::setConnection($db);

            // Create Admin User
            $userId = \GaiaAlpha\Model\User::create('admin', 'admin', 100);

            // 2. Import Package
            Output::writeln("Importing package...");
            $importer = new \GaiaAlpha\ImportExport\WebsiteImporter($packagePath, $userId, $sitePath . '/assets');
            $importer->import();

            // 3. Verify Data
            Output::writeln("Verifying data...");
            $errors = [];

            // Verify Pages
            $pages = \GaiaAlpha\Model\Page::findAllByUserId($userId);
            $pageCount = count($pages);
            Output::writeln("- Pages Imported: $pageCount");

            // Check for Home Page
            $home = \GaiaAlpha\Model\Page::findBySlug('home');
            if (!$home) {
                // Check if slug / exists
                $home = \GaiaAlpha\Model\Page::findBySlug('/');
            }
            if ($home) {
                Output::success("  - Home Page found.");

                // Check Cover Image in Frontmatter
                if (!empty($home['image'])) {
                    if (strpos($home['image'], '/media/' . $userId . '/') === 0) {
                        Output::success("  - Cover Image imported and path rewritten.");
                    } else {
                        $errors[] = "Home Page cover image path not rewritten: " . $home['image'];
                    }
                } else {
                    $errors[] = "Home Page image is missing (expected from export).";
                }
            } else {
                $errors[] = "Home Page not found.";
            }

            // Verify Menus
            $menus = \GaiaAlpha\Model\Menu::all();
            $menuCount = count($menus);
            Output::writeln("- Menus Imported: $menuCount");
            if ($menuCount === 0 && File::exists($packagePath . '/menus.json')) {
                $errors[] = "Menus present in package but not imported.";
            }

            // Verify Site Config
            $theme = DataStore::get($userId, 'user_pref', 'theme');
            if ($theme === 'enterprise-blue') {
                Output::success("  - Site Config 'theme' applied.");
            } else {
                $errors[] = "Site Config 'theme' mismatch. Expected 'enterprise-blue', got " . json_encode($theme);
            }

            // Verify Global Settings
            if (File::exists($packagePath . '/settings.json')) {
                $packageSettings = json_decode(File::read($packagePath . '/settings.json'), true);
                $importedSettings = DataStore::getAll(0, 'global_config');

                $missingKeys = array_diff(array_keys($packageSettings), array_keys($importedSettings));
                if (empty($missingKeys)) {
                    Output::success("  - Global Settings imported (" . count($packageSettings) . " keys).");
                } else {
                    $errors[] = "Missing global settings keys: " . implode(', ', $missingKeys);
                }

                // Verify specific important settings
                if (
                    isset($packageSettings['site_title']) &&
                    isset($importedSettings['site_title']) &&
                    $importedSettings['site_title'] === $packageSettings['site_title']
                ) {
                    Output::success("  - Site Title verified: " . $importedSettings['site_title']);
                } elseif (isset($packageSettings['site_title'])) {
                    $errors[] = "Site Title mismatch or not imported.";
                }
            } else {
                Output::writeln("  - No settings.json in package (legacy package).");
            }

            // Verify Assets
            // Check if assets were copied
            // Assets go to my-data/sites/<domain>/assets
            if (File::isDirectory($packagePath . '/assets')) {
                // Check a sample
                $files = File::glob($packagePath . '/assets/*');
                if (!empty($files)) {
                    $sample = basename($files[0]);
                    if (File::exists($sitePath . '/assets/' . $sample)) {
                        Output::success("  - Assets copied ($sample found).");
                    } else {
                        $errors[] = "Asset $sample not found in $sitePath/assets.";
                    }
                }
            }

            // Verify Templates
            $templates = \GaiaAlpha\Model\Template::findAllByUserId($userId);
            Output::writeln("- Templates Imported: " . count($templates));

            // Verify Forms
            $forms = \GaiaAlpha\Model\DB::fetchAll("SELECT * FROM forms WHERE user_id = ?", [$userId]);
            Output::writeln("- Forms Imported: " . count($forms));


            // 4. Report
            if (empty($errors)) {
                Output::success("Verification Passed!");
            } else {
                Output::error("Verification Failed with errors:");
                foreach ($errors as $err) {
                    Output::writeln(" - $err", 'red');
                }
            }

        } catch (\Exception $e) {
            Output::error("Verification Exception: " . $e->getMessage());
        } finally {
            // 5. Cleanup
            Output::writeln("Cleaning up temporary site...");
            // Reset DB connection
            \GaiaAlpha\Model\DB::setConnection(null); // Close connection if possible?
            // SQLite close is tricky in PHP PDO, usually nulling helper might not be enough if instance held.
            // But deleting file might fail if locked.
            unset($db);
            gc_collect_cycles();

            if (isset($sitePath) && File::exists($sitePath)) {
                self::deleteDir($sitePath);
            }
        }
    }
}
