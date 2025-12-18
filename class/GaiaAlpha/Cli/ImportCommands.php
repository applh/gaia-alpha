<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;
use GaiaAlpha\ImportExport\WebsiteImporter;
use GaiaAlpha\SiteManager;
use GaiaAlpha\File;

class ImportCommands
{
    public static function handleSite()
    {
        $inDir = Input::getOption('in');

        if (!$inDir) {
            Output::error("Usage: php cli.php import:site --in=<path> [--site=<domain>]");
            exit(1);
        }

        try {
            // We need a userId to assign content to.
            // Assuming Admin (ID 1).
            $userId = 1;

            Output::writeln("Importing site from: $inDir");

            $importer = new WebsiteImporter($inDir, $userId);
            $importer->import();

            Output::success("Site imported successfully.");
        } catch (\Exception $e) {
            Output::error("Import failed: " . $e->getMessage());
            exit(1);
        }
    }
}
