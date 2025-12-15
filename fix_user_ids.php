<?php
require_once __DIR__ . '/class/autoload.php';
\GaiaAlpha\App::cli_setup(__DIR__);

echo "Fixing User IDs...\n";

// Tables to update
$tables = ['todos', 'cms_partials', 'cms_templates', 'cms_pages', 'forms', 'map_markers'];

foreach ($tables as $table) {
    $count = \GaiaAlpha\Model\DB::fetchColumn("SELECT count(*) FROM $table WHERE user_id = 0");
    if ($count > 0) {
        echo "Updating $count rows in $table from user_id 0 to 1...\n";
        \GaiaAlpha\Model\DB::execute("UPDATE $table SET user_id = 1 WHERE user_id = 0");
    } else {
        echo "No orphaned rows in $table.\n";
    }
}

echo "Done.\n";
