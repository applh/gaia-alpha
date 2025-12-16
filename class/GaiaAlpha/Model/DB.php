<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Database;
use GaiaAlpha\Hook;
use PDO;

class DB
{


    // Instance DB removed. Static access only.
    protected static $table;
    protected static $fillable = [];

    public static function query(string $sql, array $params = [])
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $start = microtime(true);

        // Check if it's a SELECT (query) or INSERT/UPDATE/DELETE (prepare+execute)
        // Actually, prepare+execute works for SELECT too and is safer.
        // Consistency: implementation plan said "return PDOStatement".

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $duration = microtime(true) - $start;
        Hook::run('database_query_executed', $sql, $params, $duration);

        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = [], int $mode = PDO::FETCH_ASSOC)
    {
        return self::query($sql, $params)->fetchAll($mode);
    }

    public static function fetch(string $sql, array $params = [], int $mode = PDO::FETCH_ASSOC)
    {
        return self::query($sql, $params)->fetch($mode);
    }

    public static function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        return self::query($sql, $params)->fetchColumn($column);
    }

    public static function execute(string $sql, array $params = [])
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function lastInsertId()
    {
        return \GaiaAlpha\Controller\DbController::getPdo()->lastInsertId();
    }

    public static function all()
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $stmt = self::query("SELECT * FROM $table ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $stmt = self::query("SELECT * FROM $table WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $fillable = static::$fillable;

        $fields = [];
        $values = [];
        $placeholders = [];

        foreach ($fillable as $field) {
            if (isset($data[$field])) {
                $fields[] = $field;
                $values[] = $data[$field];
                $placeholders[] = '?';
            }
        }

        // Add timestamps
        $fields[] = 'created_at';
        $values[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';

        $fields[] = 'updated_at';
        $values[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';

        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        self::query($sql, $values);
        return self::lastInsertId();
    }

    public static function update($id, $data)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $fillable = static::$fillable;

        $sets = [];
        $values = [];

        foreach ($fillable as $field) {
            if (isset($data[$field])) {
                $sets[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($sets))
            return false;

        $sets[] = "updated_at = ?";
        $values[] = date('Y-m-d H:i:s');

        $values[] = $id; // For WHERE clause

        $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = self::query($sql, $values);
        return $stmt->rowCount() > 0;
    }

    public static function delete($id)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $stmt = self::query("DELETE FROM $table WHERE id = ?", [$id]);
        return true;
    }

    public static function getTables()
    {
        return self::fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name", [], \PDO::FETCH_COLUMN);
    }

    public static function getTableSchema($tableName)
    {
        return self::fetchAll("PRAGMA table_info($tableName)");
    }

    public static function getTableRecords($tableName, $limit = 100)
    {
        return self::fetchAll("SELECT * FROM $tableName LIMIT $limit");
    }

    public static function getTableCount($tableName)
    {
        return self::fetchColumn("SELECT COUNT(*) FROM $tableName");
    }
}
