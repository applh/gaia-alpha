<?php
require_once __DIR__ . '/../class/autoload.php';

use GaiaAlpha\App;
use GaiaAlpha\Database;

// Setup environment
App::web_setup(__DIR__ . '/..');

echo "Migrating: Create menus table...\n";

$db = new Database(GAIA_DB_DSN);
$pdo = $db->getPdo();

$sql = "
CREATE TABLE IF NOT EXISTS menus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    location TEXT,
    items TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $pdo->exec($sql);
    echo "Success: Table 'menus' created or already exists.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
