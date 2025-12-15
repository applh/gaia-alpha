<?php
require_once __DIR__ . '/../class/autoload.php';

use GaiaAlpha\App;
use GaiaAlpha\Database;

// Setup environment
App::web_setup(__DIR__ . '/..');

echo "Seeding: Admin Components...\n";

$db = new Database(GAIA_DB_DSN);
$pdo = $db->getPdo();

$definition = [
    "name" => "user-stats",
    "title" => "User Statistics",
    "layout" => [
        "type" => "container",
        "children" => [
            [
                "type" => "stat-card",
                "label" => "Total Users",
                "dataSource" => [
                    "type" => "api",
                    "endpoint" => "/@/v1/users/count"
                ]
            ]
        ]
    ]
];

$stmt = $pdo->prepare("INSERT INTO admin_components (name, title, description, category, icon, view_name, definition, version, enabled, admin_only) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$name = 'user-stats';
$title = 'User Statistics';
$description = 'Example user statistics component';
$category = 'examples';
$icon = 'users';
$view_name = 'user-stats';
$jsonDef = json_encode($definition, JSON_PRETTY_PRINT);
$version = 1;
$enabled = 1;
$adminOnly = 1;

try {
    $stmt->execute([$name, $title, $description, $category, $icon, $view_name, $jsonDef, $version, $enabled, $adminOnly]);
    echo "Success: Seeded 'user-stats' component.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        echo "Skipped: 'user-stats' component already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
