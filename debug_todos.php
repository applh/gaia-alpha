<?php
require_once __DIR__ . '/class/autoload.php';
\GaiaAlpha\App::cli_setup(__DIR__);

echo "Checking Todos Table...\n";
$todos = \GaiaAlpha\Model\DB::fetchAll("SELECT id, user_id, title FROM todos");

if (empty($todos)) {
    echo "No todos found in database.\n";
} else {
    echo "Found " . count($todos) . " todos:\n";
    foreach ($todos as $t) {
        echo "[{$t['id']}] User: {$t['user_id']} | Title: {$t['title']}\n";
    }
}

echo "\nChecking Users...\n";
$users = \GaiaAlpha\Model\DB::fetchAll("SELECT id, username FROM users");
foreach ($users as $u) {
    echo "[{$u['id']}] {$u['username']}\n";
}
