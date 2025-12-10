<?php
require __DIR__ . '/../class/autoload.php';
if (file_exists(__DIR__ . '/../my-config.php')) {
    require_once __DIR__ . '/../my-config.php';
}

use GaiaAlpha\Database;

if (!defined('GAIA_DB_DSN')) {
    define('GAIA_DB_PATH', __DIR__ . '/../my-data/gaia.db');
    define('GAIA_DB_DSN', 'sqlite:' . GAIA_DB_PATH);
}

try {
    $db = new Database(GAIA_DB_DSN);
    $pdo = $db->getPdo();

    echo "Running migration 010 (Add Map Markers)...\n";
    $sql = file_get_contents(__DIR__ . '/../templates/sql/migrations/010_map_markers.sql');

    $pdo->exec($sql);
    echo "Migration complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
