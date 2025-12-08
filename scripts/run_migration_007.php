<?php
require __DIR__ . '/../class/autoload.php';
require __DIR__ . '/../my-config.php';

use GaiaAlpha\Database;

try {
    $db = new Database(GAIA_DB_DSN);
    $pdo = $db->getPdo();

    echo "Running migration 007 manually...\n";
    $sql = file_get_contents(__DIR__ . '/../templates/sql/migrations/007_user_settings_schema.sql');

    $statements = explode(';', $sql);
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            echo "Executing: " . substr($stmt, 0, 50) . "...\n";
            $pdo->exec($stmt);
        }
    }
    echo "Migration complete.\n";

    // Verify again
    $stmt = $pdo->query("PRAGMA table_info(data_store)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in data_store now:\n";
    foreach ($columns as $col) {
        echo "- " . $col['name'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
