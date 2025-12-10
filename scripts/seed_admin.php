<?php
require_once __DIR__ . '/../class/autoload.php';

use GaiaAlpha\App;
use GaiaAlpha\Database;

// Setup environment
App::web_setup(__DIR__ . '/..');

echo "Seeding Admin User...\n";

$db = new Database(GAIA_DB_DSN);
$pdo = $db->getPdo();

$username = 'admin';
$password = 'admin';
$level = 100;

// Check if exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Admin user already exists. Updating level to 100...\n";
    $stmt = $pdo->prepare("UPDATE users SET level = 100, password_hash = ? WHERE username = ?");
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $username]);
} else {
    echo "Creating admin user...\n";
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, level, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $level]);
}

echo "Done. You can now login with:\nUsername: admin\nPassword: admin\n";
