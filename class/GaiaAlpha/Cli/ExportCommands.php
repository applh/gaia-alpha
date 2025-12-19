<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;
use GaiaAlpha\ImportExport\WebsiteExporter;
use GaiaAlpha\SiteManager;
use GaiaAlpha\Env;
use GaiaAlpha\Session;

class ExportCommands
{
    public static function handleSite()
    {
        $outDir = Input::getOption('out');
        $site = Input::getOption('site');

        if (!$outDir) {
            Output::error("Usage: php cli.php export:site --out=<path> [--site=<domain>]");
            exit(1);
        }

        // Output directory must be absolute or relative to CWD
        // Input::getOption returns raw string. 
        // We should resolve it to absolute path if possible or keep relative

        // Ensure site is resolved if current logic depends on SiteManager
        // SiteManager::resolve() is called in App::cli_setup, checking args.
        // It should have already picked up --site if present.

        $currentSite = SiteManager::getCurrentSite();
        if ($site && $currentSite !== $site) {
            // If App setup didn't pick it up for some reason (maybe it only checks 'site' arg name?)
            // SiteManager checks 'site' option. So it should be fine.
            // But we can verify.
            Output::info("Exporting site: " . ($currentSite ?: 'default'));
        }

        try {
            // We need a userId to export content for.
            // In CLI, we might be "admin" (ID 1) by default or allow specifying user.
            // For now, let's assume Super Admin / ID 1 export.
            $userId = 1;

            Output::writeln("Exporting site to: $outDir");

            $assetsDir = null;
            if ($currentSite) {
                $rootDir = Env::get('root_dir');
                $assetsDir = $rootDir . '/my-data/sites/' . $currentSite . '/assets';
            }

            $exporter = new WebsiteExporter($outDir, $userId, $assetsDir);
            $exporter->export();

            Output::success("Site exported successfully.");
        } catch (\Exception $e) {
            Output::error("Export failed: " . $e->getMessage());
            exit(1);
        }
    }
}
