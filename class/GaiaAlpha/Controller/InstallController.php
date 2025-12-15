<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Router;
use GaiaAlpha\Env;

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

    public function install()
    {
        // If already installed, forbid
        if (self::isInstalled()) {
            $this->jsonResponse(['error' => 'Application already installed'], 403);
            return;
        }

        $data = $this->getJsonInput();

        if (empty($data['username']) || empty($data['password'])) {
            $this->jsonResponse(['error' => 'Missing username or password'], 400);
            return;
        }

        try {
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
            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Failed to create user: ' . $e->getMessage()], 400);
        }
    }

    public function registerRoutes()
    {
        Router::add('GET', '/install', [$this, 'index']);
        Router::add('POST', '/@/install', [$this, 'install']);
    }

    // Static check meant to be run as a framework task
    public static function checkInstalled()
    {
        // We only check this for web requests, not CLI (CLI might be used to fix issues)
        if (php_sapi_name() === 'cli') {
            return;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Allow static assets, install page, and install API
        // Also allow debug/min paths if needed?
        if (
            $uri === '/install' ||
            $uri === '/@/install' ||
            strpos($uri, '/assets/') === 0 ||
            strpos($uri, '/min/') === 0 ||
            strpos($uri, '/favicon.ico') === 0
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
        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : Env::get('root_dir') . '/my-data';
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
        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : Env::get('root_dir') . '/my-data';
        if (is_dir($dataPath)) {
            touch($dataPath . '/installed.lock');
        }
    }

}
