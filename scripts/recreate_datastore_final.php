<?php
require __DIR__ . '/../class/autoload.php';
require __DIR__ . '/../my-config.php';

use GaiaAlpha\Database;

try {
    $db = new Database(GAIA_DB_DSN);
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Dropping existing tables if any...\n";
    $pdo->exec("DROP TABLE IF EXISTS data_store");
    $pdo->exec("DROP TABLE IF EXISTS data_store_new");

    echo "Creating data_store table...\n";
    $sql = "CREATE TABLE data_store (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        type TEXT NOT NULL,
        key TEXT NOT NULL,
        value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, type, key)
    )";
    $pdo->exec($sql);

    echo "Table created.\n";

    // Check
    $stmt = $pdo->query("PRAGMA table_info(data_store)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($columns)) {
        echo "ERROR: Table still missing!\n";
    } else {
        echo "Success! Columns:\n";
        foreach ($columns as $col) {
            echo "- " . $col['name'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
