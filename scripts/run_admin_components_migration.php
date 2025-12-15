<?php
require_once __DIR__ . '/../class/autoload.php';

use GaiaAlpha\App;
use GaiaAlpha\Database;

// Setup environment
App::web_setup(__DIR__ . '/..');

echo "Migrating: Create Admin Component Builder tables...\n";

$db = new Database(GAIA_DB_DSN);
$pdo = $db->getPdo();

$sqlFile = __DIR__ . '/../templates/sql/011_admin_components.sql';
if (!file_exists($sqlFile)) {
    echo "Error: SQL file not found: $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "Success: Admin Component Builder tables created.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
