<?php
require __DIR__ . '/../class/autoload.php';
require __DIR__ . '/../my-config.php';

use GaiaAlpha\Database;

try {
    $db = new Database(GAIA_DB_DSN);
    $pdo = $db->getPdo();

    $stmt = $pdo->query("PRAGMA table_info(data_store)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in data_store:\n";
    foreach ($columns as $col) {
        echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
