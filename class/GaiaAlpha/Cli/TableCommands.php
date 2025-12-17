<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;

use GaiaAlpha\Cli\Input;

class TableCommands
{
    public static function handleList(): void
    {
        if (!Input::has(0))
            die("Missing table name.\n");

        $table = Input::get(0);
        $count = \GaiaAlpha\Model\DB::fetchColumn("SELECT count(*) FROM $table");
        $rows = \GaiaAlpha\Model\DB::fetchAll("SELECT * FROM $table LIMIT 10");
        echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    }

    public static function handleInsert(): void
    {
        if (!Input::has(0) || !Input::has(1))
            die("Usage: table:insert <table> <json_data>\n");

        $table = Input::get(0);
        $data = json_decode(Input::get(1), true);
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
        if (!Input::has(0) || !Input::has(1))
            die("Usage: table:update <table> <id> key=value key2=value2...\n");

        $table = Input::get(0);
        $id = Input::get(1);

        // Parse key=value pairs
        $data = [];
        $args = Input::all();
        for ($i = 2; $i < count($args); $i++) {
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
        if (!Input::has(0) || !Input::has(1))
            die("Usage: table:delete <table> <id>\n");

        $table = Input::get(0);
        $id = Input::get(1);

        \GaiaAlpha\Model\DB::execute("DELETE FROM $table WHERE id = ?", [$id]);

        echo "Row deleted.\n";
    }

    public static function handleQuery(): void
    {
        if (!Input::has(0))
            die("Missing SQL query.\n");

        $sql = Input::get(0);

        if (stripos(trim($sql), 'SELECT') === 0) {
            $rows = \GaiaAlpha\Model\DB::fetchAll($sql);
            echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
        } else {
            $affectedRows = \GaiaAlpha\Model\DB::execute($sql);
            echo "Query executed. Rows affected: " . $affectedRows . "\n";
        }
    }
}
