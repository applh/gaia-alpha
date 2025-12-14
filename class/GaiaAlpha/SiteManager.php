<?php

namespace GaiaAlpha;

class SiteManager
{
    private static ?string $currentSite = null;
    private static ?string $dbPath = null;

    /**
     * Resolve the current site based on environment (Web/CLI)
     */
    public static function resolve()
    {
        $rootDir = Env::get('root_dir');
        $sitesDir = $rootDir . '/my-data/sites';

        // Ensure sites directory exists
        if (!is_dir($sitesDir)) {
            mkdir($sitesDir, 0755, true);
        }

        // 1. CLI Override Logic
        if (php_sapi_name() === 'cli') {
            // Check for --site=domain.com argument
            global $argv;
            foreach ($argv as $arg) {
                if (str_starts_with($arg, '--site=')) {
                    $domain = substr($arg, 7);
                    self::setSite($domain, $sitesDir);
                    return;
                }
            }
        }

        // 2. HTTP Host Logic
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            // Remove port if present
            $parts = explode(':', $host);
            $domain = $parts[0];

            // Normalize: remove www.
            if (str_starts_with($domain, 'www.')) {
                $domain = substr($domain, 4);
            }

            self::setSite($domain, $sitesDir);
            return;
        }

        // 3. Fallback / Default
        // If we are here, we are likely in a CLI command without a site arg, 
        // or a default environment.
        // For backwards compatibility, the "default" database is in my-data/database.sqlite
        // But for consistency, we might want to treat 'localhost' as default?
        // Let's stick to the existing behavior: if no site resolved, do NOT set GAIA_DB_PATH
        // and let DbController fall back to its default (database.sqlite).
    }

    private static function setSite(string $domain, string $sitesDir)
    {
        // Sanitize
        $domain = preg_replace('/[^a-zA-Z0-9.-]/', '', $domain);

        $siteDb = $sitesDir . '/' . $domain . '.sqlite';

        // Check if specific site DB exists
        if (file_exists($siteDb)) {
            self::$currentSite = $domain;
            self::$dbPath = $siteDb;
            // Define Constant for DbController to pick up
            if (!defined('GAIA_DB_PATH')) {
                define('GAIA_DB_PATH', $siteDb);
            }
        } else {
            // If explicit site requested but not found...
            // For Web: Fallback to default? Or 404? 
            // If I access specific.domain.com and it's not setup, standard behavior is often generic catch-all or error.
            // Let's allow fallback to default DB if not found, 
            // OR strictly enforce: "If you want a site, create it first".

            // DECISION: If not found, do nothing -> System falls back to default database.sqlite
            // This allows 'localhost' to keep working without a 'localhost.sqlite'.
        }
    }

    public static function getCurrentSite(): ?string
    {
        return self::$currentSite;
    }

    public static function getDbPath(): ?string
    {
        return self::$dbPath;
    }
}
