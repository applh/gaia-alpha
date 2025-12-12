<?php

require_once __DIR__ . '/../class/autoload.php';

use GaiaAlpha\Controller\DbController;
use GaiaAlpha\App;

// Bootstrap App to set environment variables (e.g. path_data)
App::web_setup(dirname(__DIR__));

echo "Migrating Chat Database...\n";

$pdo = DbController::getPdo();

$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(sender_id) REFERENCES users(id),
    FOREIGN KEY(receiver_id) REFERENCES users(id)
)";

try {
    $pdo->exec($sql);
    echo "Table 'messages' created successfully.\n";

    // Add indexes for performance
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_messages_sender ON messages(sender_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_messages_receiver ON messages(receiver_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_messages_created ON messages(created_at)");
    echo "Indexes created.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
