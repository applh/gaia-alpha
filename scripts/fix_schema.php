<?php
require __DIR__ . '/../class/autoload.php';
require __DIR__ . '/../my-config.php';

use GaiaAlpha\Database;

try {
    $db = new Database(GAIA_DB_DSN);
    $pdo = $db->getPdo();

    echo "Applying 004_data_store.sql...\n";
    $sql4 = file_get_contents(__DIR__ . '/../templates/sql/004_data_store.sql');
    $pdo->exec($sql4);
    echo "004 applied.\n";

    echo "Applying 007_user_settings_schema.sql...\n";
    $sql7 = file_get_contents(__DIR__ . '/../templates/sql/migrations/007_user_settings_schema.sql');

    // Split by semicolon because PDO::exec sometimes likes single statements for complex ops, though exec supports multiple.
    // But migration generic logic splits them. Let's replicate strict logic.
    $statements = array_filter(array_map('trim', explode(';', $sql7)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            // Check if we need to handle specific errors (like table already exists for data_store_new if previous run failed)
            try {
                echo "Executing: " . substr($stmt, 0, 50) . "...\n";
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                echo "Warning on statement: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "007 applied.\n";

    // Verify
    $stmt = $pdo->query("PRAGMA table_info(data_store)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in data_store now:\n";
    foreach ($columns as $col) {
        echo "- " . $col['name'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
