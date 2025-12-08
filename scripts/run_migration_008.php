<?php
require __DIR__ . '/../class/autoload.php';
// require __DIR__ . '/../my-config.php'; // Might not be needed if App.php loads it? But safe to include if App isn't bootstrapped.
// autoload usually includes App.php which includes my-config logic if refactored.
// Checking run_migration_007 it includes my-config.php explicitly.

// Check if my-config.php exists
if (file_exists(__DIR__ . '/../my-config.php')) {
    require_once __DIR__ . '/../my-config.php';
}

use GaiaAlpha\Database;

// Fallback constant if not defined manually (though App should usually handle this)
if (!defined('GAIA_DB_DSN')) {
    // Basic fallback for development
    define('GAIA_DB_PATH', __DIR__ . '/../my-data/gaia.db');
    define('GAIA_DB_DSN', 'sqlite:' . GAIA_DB_PATH);
}

try {
    $db = new Database(GAIA_DB_DSN);
    $pdo = $db->getPdo();

    echo "Running migration 008 (Forms) manually...\n";
    $sql = file_get_contents(__DIR__ . '/../templates/sql/migrations/008_create_forms_tables.sql');

    $statements = explode(';', $sql);
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            echo "Executing: " . substr($stmt, 0, 50) . "...\n";
            $pdo->exec($stmt);
        }
    }
    echo "Migration complete.\n";

    // Verify
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('forms', 'form_submissions')");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables created: " . implode(', ', $tables) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
