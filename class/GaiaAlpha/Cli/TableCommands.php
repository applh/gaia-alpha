<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;
use PDO;
use GaiaAlpha\Controller\DbController;

class TableCommands
{


    public static function handleList(): void
    {
        global $argv;
        $args = $argv;
        if (!isset($args[2]))
            die("Missing table name.\n");
        $table = $args[2];
        $count = \GaiaAlpha\Model\DB::fetchColumn("SELECT count(*) FROM $table");
        $rows = \GaiaAlpha\Model\DB::fetchAll("SELECT * FROM $table LIMIT 10");
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
        \GaiaAlpha\Model\DB::execute($sql, array_values($data));

        echo "Row inserted. ID: " . \GaiaAlpha\Model\DB::lastInsertId() . "\n";
    }

    public static function handleUpdate(): void
    {
        global $argv;
        $args = $argv;
        if (!isset($args[2]) || !isset($args[3]))
            die("Usage: table:update <table> <id> key=value key2=value2...\n");
        $table = $args[2];
        $id = $args[3];

        // Parse key=value pairs
        $data = [];
        for ($i = 4; $i < count($args); $i++) {
            if (strpos($args[$i], '=') !== false) {
                list($key, $value) = explode('=', $args[$i], 2);
                $data[$key] = $value;
            }
        }

        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "$key = ?";
        }
        $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;

        \GaiaAlpha\Model\DB::execute($sql, $values);

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

        \GaiaAlpha\Model\DB::execute("DELETE FROM $table WHERE id = ?", [$id]);

        echo "Row deleted.\n";
    }

    public static function handleQuery(): void
    {
        global $argv;
        $args = $argv;
        if (!isset($args[2]))
            die("Missing SQL query.\n");
        $sql = $args[2];
        $result = \GaiaAlpha\Model\DB::query($sql);
        $rows = $result->fetchAll(\PDO::FETCH_ASSOC); // DB::query returns statement

        if (stripos(trim($sql), 'SELECT') !== 0) {
            echo "Query executed. Rows affected: " . $result->rowCount() . "\n";
        } else {
            echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
        }
    }
}
