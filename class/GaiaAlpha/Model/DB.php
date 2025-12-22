<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Database;
use GaiaAlpha\Env;
use GaiaAlpha\Hook;
use PDO;

class DB
{
    private static ?Database $instance = null;

    // Instance DB removed. Static access only.
    protected static $table;
    protected static $fillable = [];

    public static function connect(): Database
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dataPath = Env::get('path_data');
        // Resolve path logic similar to how DSN is built
        $dbPath = defined('GAIA_DB_PATH') ? GAIA_DB_PATH : $dataPath . '/database.sqlite';

        // Check if DB exists before PDO potentially creates it
        $installNeeded = !file_exists($dbPath) || filesize($dbPath) === 0;

        $dsn = defined('GAIA_DB_DSN') ? GAIA_DB_DSN : 'sqlite:' . $dbPath;
        $user = defined('GAIA_DB_USER') ? GAIA_DB_USER : null;
        $pass = defined('GAIA_DB_PASS') ? GAIA_DB_PASS : null;

        $db = new Database($dsn, $user, $pass);

        if ($installNeeded) {
            $db->ensureSchema();
        } else {
            $db->runMigrations();
        }

        self::$instance = $db;
        return $db;
    }

    public static function setConnection(?Database $db)
    {
        self::$instance = $db;
    }

    public static function getPdo(): PDO
    {
        return self::connect()->getPdo();
    }

    /**
     * Execute a raw SQL query and return the statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $db = self::getPdo();
        $start = microtime(true);

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $duration = microtime(true) - $start;
        Hook::run('database_query_executed', $sql, $params, $duration);

        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = [], int $mode = PDO::FETCH_ASSOC): array
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
        return self::getPdo()->lastInsertId();
    }

    public static function all()
    {
        $db = self::getPdo();
        $table = static::$table;
        $stmt = self::query("SELECT * FROM $table ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $db = self::getPdo();
        $table = static::$table;
        $stmt = self::query("SELECT * FROM $table WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data)
    {
        $db = self::getPdo();
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
        $db = self::getPdo();
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
        $db = self::getPdo();
        $table = static::$table;
        $stmt = self::query("DELETE FROM $table WHERE id = ?", [$id]);
        return true;
    }

    public static function getTables()
    {
        return self::connect()->getTables();
    }

    public static function getTableSchema($tableName)
    {
        return self::connect()->getTableSchema($tableName);
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
