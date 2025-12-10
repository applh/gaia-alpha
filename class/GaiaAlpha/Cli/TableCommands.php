<?php

namespace GaiaAlpha\Cli;

use PDO;
use GaiaAlpha\Controller\DbController;

class TableCommands
{
    private static function getPdo(): PDO
    {
        return DbController::connect()->getPdo();
    }

    public static function handleList(): void
    {
        global $argv;
        $args = $argv;
        if (!isset($args[2]))
            die("Missing table name.\n");
        $table = $args[2];
        $stmt = self::getPdo()->prepare("SELECT * FROM $table");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    }

    public static function handleInsert(): void
    {
        global $argv;
        $args = $argv;
        if (!isset($args[2]) || !isset($args[3]))
            die("Usage: table:insert <table> <json_data>\n");
        $table = $args[2];
        $data = json_decode($args[3], true);
        if (!$data)
            die("Invalid JSON data.\n");

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute(array_values($data));

        echo "Row inserted. ID: " . self::getPdo()->lastInsertId() . "\n";
    }

    public static function handleUpdate(): void
    {
        global $argv;
        $args = $argv;
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

        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($values);

        echo "Row updated.\n";
    }

    public static function handleDelete(): void
    {
        global $argv;
        $args = $argv;
        if (!isset($args[2]) || !isset($args[3]))
            die("Usage: table:delete <table> <id>\n");
        $table = $args[2];
        $id = $args[3];

        $stmt = self::getPdo()->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);

        echo "Row deleted.\n";
    }

    public static function handleSql(): void
    {
        global $argv;
        $args = $argv;
        if (!isset($args[2]))
            die("Missing SQL query.\n");
        $sql = $args[2];

        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute();

        if (stripos(trim($sql), 'SELECT') === 0) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Query executed. Rows affected: " . $stmt->rowCount() . "\n";
        }
    }
}
