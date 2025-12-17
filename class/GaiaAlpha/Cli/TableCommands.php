<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class TableCommands
{
    public static function handleList(): void
    {
        if (!Input::has(0)) {
            Output::error("Missing table name.");
            exit(1);
        }

        $table = Input::get(0);
        $rows = \GaiaAlpha\Model\DB::fetchAll("SELECT * FROM $table LIMIT 10");

        if (empty($rows)) {
            Output::info("Table '$table' is empty.");
            return;
        }

        $headers = array_keys($rows[0]);
        Output::title("Table: $table (Last 10 rows)");
        Output::table($headers, $rows);
    }

    public static function handleInsert(): void
    {
        if (!Input::has(0) || !Input::has(1)) {
            Output::writeln("Usage: table:insert <table> <json_data>");
            exit(1);
        }

        $table = Input::get(0);
        $data = json_decode(Input::get(1), true);
        if (!$data) {
            Output::error("Invalid JSON data.");
            exit(1);
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        \GaiaAlpha\Model\DB::execute($sql, array_values($data));

        Output::success("Row inserted into '$table'. ID: " . \GaiaAlpha\Model\DB::lastInsertId());
    }

    public static function handleUpdate(): void
    {
        if (!Input::has(0) || !Input::has(1)) {
            Output::writeln("Usage: table:update <table> <id> key=value key2=value2...");
            exit(1);
        }

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

        if (empty($data)) {
            Output::error("No data provided to update.");
            exit(1);
        }

        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "$key = ?";
        }
        $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;

        \GaiaAlpha\Model\DB::execute($sql, $values);

        Output::success("Row $id updated in '$table'.");
    }

    public static function handleDelete(): void
    {
        if (!Input::has(0) || !Input::has(1)) {
            Output::writeln("Usage: table:delete <table> <id>");
            exit(1);
        }

        $table = Input::get(0);
        $id = Input::get(1);

        \GaiaAlpha\Model\DB::execute("DELETE FROM $table WHERE id = ?", [$id]);

        Output::success("Row $id deleted from '$table'.");
    }

    public static function handleQuery(): void
    {
        if (!Input::has(0)) {
            Output::error("Missing SQL query.");
            exit(1);
        }

        $sql = Input::get(0);

        if (stripos(trim($sql), 'SELECT') === 0) {
            $rows = \GaiaAlpha\Model\DB::fetchAll($sql);
            if (empty($rows)) {
                Output::info("No results found.");
            } else {
                $headers = array_keys($rows[0]);
                Output::table($headers, $rows);
            }
        } else {
            $affectedRows = \GaiaAlpha\Model\DB::execute($sql);
            Output::success("Query executed. Rows affected: " . $affectedRows);
        }
    }
}
