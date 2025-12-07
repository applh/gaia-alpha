<?php

require_once __DIR__ . '/class/GaiaAlpha/Database.php';

use GaiaAlpha\Database;

// Simple CLI tool for generic database operations
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

if ($argc < 2) {
    showHelp();
    exit(1);
}

try {
    $db = new Database(__DIR__ . '/my-data/database.sqlite');
    $pdo = $db->getPdo();
    $command = $argv[1];

    switch ($command) {
        case 'table:list':
            handleTableList($pdo, $argv);
            break;
        case 'table:insert':
            handleTableInsert($pdo, $argv);
            break;
        case 'table:update':
            handleTableUpdate($pdo, $argv);
            break;
        case 'table:delete':
            handleTableDelete($pdo, $argv);
            break;
        case 'sql':
            handleSql($pdo, $argv);
            break;
        case 'help':
            showHelp();
            break;
        default:
            echo "Unknown command: $command\n";
            showHelp();
            exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

function showHelp()
{
    echo "Usage: php cli.php <command> [arguments]\n\n";
    echo "Commands:\n";
    echo "  table:list <table>                  List all rows in a table\n";
    echo "  table:insert <table> <json_data>    Insert a row (e.g. '{\"col\":\"val\"}')\n";
    echo "  table:update <table> <id> <json>    Update a row by ID\n";
    echo "  table:delete <table> <id>           Delete a row by ID\n";
    echo "  sql <query>                         Execute a raw SQL query\n";
    echo "  help                                Show this help message\n";
}

function handleTableList($pdo, $args)
{
    if (!isset($args[2]))
        die("Missing table name.\n");
    $table = $args[2];
    $stmt = $pdo->prepare("SELECT * FROM $table");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
}

function handleTableInsert($pdo, $args)
{
    if (!isset($args[2]) || !isset($args[3]))
        die("Usage: table:insert <table> <json_data>\n");
    $table = $args[2];
    $data = json_decode($args[3], true);
    if (!$data)
        die("Invalid JSON data.\n");

    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    echo "Row inserted. ID: " . $pdo->lastInsertId() . "\n";
}

function handleTableUpdate($pdo, $args)
{
    if (!isset($args[2]) || !isset($args[3]) || !isset($args[4]))
        die("Usage: table:update <table> <id> <json_data>\n");
    $table = $args[2];
    $id = $args[3];
    $data = json_decode($args[4], true);
    if (!$data)
        die("Invalid JSON data.\n");

    $sets = [];
    foreach (array_keys($data) as $col) {
        $sets[] = "$col = ?";
    }
    $setString = implode(', ', $sets);

    $sql = "UPDATE $table SET $setString WHERE id = ?";
    $values = array_values($data);
    $values[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    echo "Row updated.\n";
}

function handleTableDelete($pdo, $args)
{
    if (!isset($args[2]) || !isset($args[3]))
        die("Usage: table:delete <table> <id>\n");
    $table = $args[2];
    $id = $args[3];

    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$id]);

    echo "Row deleted.\n";
}

function handleSql($pdo, $args)
{
    if (!isset($args[2]))
        die("Missing SQL query.\n");
    $sql = $args[2];

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();

    if (stripos(trim($sql), 'SELECT') === 0) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Query executed. Rows affected: " . $stmt->rowCount() . "\n";
    }
}
