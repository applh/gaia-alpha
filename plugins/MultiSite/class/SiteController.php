<?php

namespace MultiSite;

use GaiaAlpha\File;
use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use GaiaAlpha\Env;
use GaiaAlpha\Response;
use GaiaAlpha\Framework;
use GaiaAlpha\SiteManager;

class SiteController extends BaseController
{
    protected function requireAdmin()
    {
        \GaiaAlpha\Session::requireLevel(100);
    }

    public function registerRoutes()
    {
        Router::get('/@/admin/sites', [$this, 'list']);
        Router::post('/@/admin/sites', [$this, 'create']);
        Router::delete('/@/admin/sites/(.+)', [$this, 'delete']);
    }

    public function list()
    {
        $this->requireAdmin();
        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        $sites = [];
        if (File::isDirectory($sitesDir)) {
            foreach (File::glob($sitesDir . '/*.sqlite') as $dbFile) {
                $domain = basename($dbFile, '.sqlite');
                $size = File::size($dbFile);
                $sites[] = [
                    'domain' => $domain,
                    'size' => $size,
                    'created_at' => filectime($dbFile)
                ];
            }
        }

        Response::json($sites);
    }

    public function create()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        $domain = $data['domain'] ?? '';
        // Validate domain format (alphanumeric, dots, hyphens)
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            Response::json(['error' => 'Invalid domain format'], 400);
            return;
        }

        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        if (!File::isDirectory($sitesDir)) {
            File::makeDirectory($sitesDir);
        }

        $dbPath = $sitesDir . '/' . $domain . '.sqlite';

        if (File::exists($dbPath)) {
            Response::json(['error' => 'Site already exists'], 409);
            return;
        }

        // Create empty SQLite file
        try {
            $pdo = new \PDO("sqlite:$dbPath");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Should we run migrations? 
            // GaiaAlpha architecture usually auto-migrates on first connect via DbController.
            // But since this is a separate DB file, DbController needs to connect TO IT to run migrations.
            // Since we are just provisioning the file, we can let the first visit to that domain trigger the migration 
            // via standard DbController::connect() flow which checks schema.
            // OR we can force it now if we want to ensure it's ready.
            // For now, let's just create the file (PDO connect does that).
            // We can optionally seed it?

            Response::json(['success' => true, 'domain' => $domain]);
        } catch (\Exception $e) {
            Response::json(['error' => 'Failed to create site database: ' . $e->getMessage()], 500);
        }
    }

    public function delete($domain)
    {
        $this->requireAdmin();

        // Sanitize path traversal
        $domain = basename($domain);

        $rootDir = Env::get('root_dir');
        $file = $rootDir . '/my-data/sites/' . $domain . '.sqlite';

        if (File::exists($file)) {
            if (File::delete($file)) {
                Response::json(['success' => true]);
            } else {
                Response::json(['error' => 'Failed to delete file'], 500);
            }
        } else {
            Response::json(['error' => 'Site not found'], 404);
        }
    }
}
