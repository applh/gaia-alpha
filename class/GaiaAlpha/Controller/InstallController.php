<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Router;
use GaiaAlpha\Env;
use GaiaAlpha\Response;
use GaiaAlpha\Request;

class InstallController extends BaseController
{
    public function index()
    {
        // If already installed, redirect home
        if (self::isInstalled()) {
            header('Location: /');
            exit;
        }

        $rootDir = Env::get('root_dir');
        include $rootDir . '/templates/install.php';
    }

    public function getPlugins()
    {
        $rootDir = Env::get('root_dir');
        $pathData = Env::get('path_data');
        $pluginDirs = [
            $pathData . '/plugins',
            $rootDir . '/plugins'
        ];

        // Gather all unique plugins
        $plugins = [];
        foreach ($pluginDirs as $dir) {
            if (!is_dir($dir))
                continue;
            foreach (glob($dir . '/*/plugin.json') as $file) {
                $dirName = basename(dirname($file));
                if (!isset($plugins[$dirName])) {
                    $meta = json_decode(file_get_contents($file), true);
                    // Normalize type
                    $type = $meta['type'] ?? (($meta['is_core'] ?? false) ? 'core' : 'standard');

                    $plugins[$dirName] = [
                        'id' => $dirName,
                        'name' => $meta['name'] ?? $dirName,
                        'description' => $meta['description'] ?? '',
                        'type' => $type
                    ];
                }
            }
        }

        Response::json(array_values($plugins));
    }

    public function install()
    {
        // If already installed, forbid
        if (self::isInstalled()) {
            Response::json(['error' => 'Application already installed'], 403);
            return;
        }

        $data = Request::input();

        if (empty($data['username']) || empty($data['password'])) {
            Response::json(['error' => 'Missing username or password'], 400);
            return;
        }

        try {
            // Validate Database Connection first
            $dbType = $data['db_type'] ?? 'sqlite';
            $dsn = '';
            $user = null;
            $pass = null;

            $dataPath = Env::get('path_data');
            if (!$dataPath) {
                $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : Env::get('root_dir') . '/my-data';
            }
            if (!is_dir($dataPath)) {
                mkdir($dataPath, 0755, true);
            }

            if ($dbType === 'sqlite') {
                $dsn = 'sqlite:' . $dataPath . '/database.sqlite';
            } else {
                $host = $data['db_host'] ?? '127.0.0.1';
                // Port default logic
                if (empty($data['db_port'])) {
                    $port = ($dbType === 'mysql') ? '3306' : '5432';
                } else {
                    $port = $data['db_port'];
                }

                $name = $data['db_name'] ?? 'gaia_alpha';
                $user = $data['db_user'] ?? '';
                $pass = $data['db_pass'] ?? '';

                if ($dbType === 'mysql') {
                    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
                } elseif ($dbType === 'pgsql') {
                    $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                }
            }

            // Test Connection and Initialize Schema
            // We pass parameters to the Database constructor to test connectivity
            // Error will be caught by the catch block below
            $db = new \GaiaAlpha\Database($dsn, $user, $pass);
            $db->ensureSchema();

            // Write Configuration to my-data/config.php
            $configContent = "<?php\n\n";
            $configContent .= "// Database Configuration ($dbType)\n";
            if ($dbType === 'sqlite') {
                // Use __DIR__ so it's relative to the config file location
                $configContent .= "define('GAIA_DB_DSN', 'sqlite:' . __DIR__ . '/database.sqlite');\n";
            } else {
                $configContent .= "define('GAIA_DB_DSN', '$dsn');\n";
                $configContent .= "define('GAIA_DB_USER', '$user');\n";
                $configContent .= "define('GAIA_DB_PASS', '$pass');\n";
            }

            file_put_contents($dataPath . '/config.php', $configContent);

            // Re-inject DB instance into Model Layer
            \GaiaAlpha\Model\DB::setConnection($db);

            // Save Active Plugins
            $plugins = [];
            if (isset($data['plugins']) && is_array($data['plugins'])) {
                $plugins = $data['plugins'];
            }
            file_put_contents($dataPath . '/active_plugins.json', json_encode($plugins));

            // Create Admin User (Level 100)
            $id = User::create($data['username'], $data['password'], 100);

            // Create App Page if requested
            if (!empty($data['create_app'])) {
                $slug = $data['app_slug'] ?? 'app';
                // Basic validation for slug
                $slug = preg_replace('/[^a-z0-9-_]/', '', strtolower($slug));
                if (empty($slug))
                    $slug = 'app';

                \GaiaAlpha\Model\Page::create($id, [
                    'title' => 'App Dashboard',
                    'slug' => $slug,
                    'content' => '',
                    'cat' => 'page',
                    'template_slug' => 'app'
                ]);
            }

            // Seed Demo Data if requested
            if (!empty($data['demo_data'])) {
                \GaiaAlpha\Seeder::run($id);
            }

            // Save Site Settings
            $siteTitle = $data['site_title'] ?? 'Gaia Alpha';
            $siteDesc = $data['site_description'] ?? 'The unified open-source operating system.';

            \GaiaAlpha\Model\DataStore::set(0, 'global_config', 'site_title', $siteTitle);
            \GaiaAlpha\Model\DataStore::set(0, 'global_config', 'site_description', $siteDesc);

            // Auto login? For now let client handle redirect.

            $this->markInstalled();
            Response::json(['success' => true]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Failed to create user: ' . $e->getMessage()], 400);
        }
    }

    public function registerRoutes()
    {
        Router::add('GET', '/install', [$this, 'index']);
        Router::add('POST', '/@/install', [$this, 'install']);
        Router::add('GET', '/@/install/plugins', [$this, 'getPlugins']);
    }

    // Static check meant to be run as a framework task
    public static function checkInstalled()
    {
        // We only check this for web requests, not CLI (CLI might be used to fix issues)
        if (php_sapi_name() === 'cli') {
            return;
        }

        $uri = Request::path();

        // Allow static assets, install page, and install API
        // Also allow debug/min paths if needed?
        if (
            $uri === '/install' ||
            str_starts_with($uri, '/@/install') ||
            str_starts_with($uri, '/assets/') ||
            str_starts_with($uri, '/min/') ||
            str_starts_with($uri, '/favicon.ico')
        ) {
            return;
        }

        if (self::isInstalled()) {
            return;
        }

        // If we got here, not installed
        header('Location: /install');
        exit;
    }

    private static function isInstalled()
    {
        $dataPath = Env::get('path_data');
        if (!$dataPath) {
            $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : Env::get('root_dir') . '/my-data';
        }
        $lockFile = $dataPath . '/installed.lock';

        if (file_exists($lockFile)) {
            return true;
        }

        try {
            if (User::count() > 0) {
                // Self-heal: create lock file
                if (is_dir($dataPath)) {
                    touch($lockFile);
                }
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    private function markInstalled()
    {
        $dataPath = Env::get('path_data');
        if (!$dataPath) {
            $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : Env::get('root_dir') . '/my-data';
        }

        if (is_dir($dataPath)) {
            touch($dataPath . '/installed.lock');
        }
    }

}
