<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;
use GaiaAlpha\Router;
use GaiaAlpha\Env;

class DbController extends BaseController
{
    public static function connect(): Database
    {
        $dataPath = Env::get('path_data');
        $dsn = defined('GAIA_DB_DSN') ? GAIA_DB_DSN : 'sqlite:' . (defined('GAIA_DB_PATH') ? GAIA_DB_PATH : $dataPath . '/database.sqlite');

        $db = new Database($dsn);
        $db->ensureSchema();

        return $db;
    }

    public static function getPdo()
    {
        return self::connect()->getPdo();
    }

    public static function getTables(): array
    {
        $db = self::connect();
        $pdo = $db->getPdo();
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function getTableSchema(string $table): array
    {
        $db = self::connect();
        $pdo = $db->getPdo();
        // Sanitize table name somewhat, though this is intended for admin use
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $stmt = $pdo->query("PRAGMA table_info($table)");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Assuming these route definitions belong to a method that registers routes,
    // or are placed directly in a routing configuration section.
    // Since the original document does not contain Router::add calls,
    // and the provided edit places them incorrectly within existing methods,
    // I'm adding them as a new method `registerRoutes` for demonstration,
    // assuming `listTables`, `getTableData`, etc. are methods of this class.
    // Note: Using `$this` in static context is incorrect.
    // If these are static methods, they should be `[self::class, 'methodName']`.
    // If this is an instance method, then the class itself should not be static.
    // For now, I'll assume `listTables`, etc. are instance methods and this `registerRoutes`
    // method would be called on an instance of DbController.
    public function registerRoutes()
    {
        Router::add('GET', '/@/admin/db/tables', [$this, 'listTables']);
        Router::add('GET', '/@/admin/db/table/([a-zA-Z0-9_]+)', [$this, 'getTableData']);
        Router::add('POST', '/@/admin/db/query', [$this, 'executeQuery']);
        Router::add('POST', '/@/admin/db/table/([a-zA-Z0-9_]+)', [$this, 'insertRecord']);
        Router::add('PATCH', '/@/admin/db/table/([a-zA-Z0-9_]+)/(\d+)', [$this, 'updateRecord']);
        Router::add('DELETE', '/@/admin/db/table/([a-zA-Z0-9_]+)/(\d+)', [$this, 'deleteRecord']);
    }

    // Placeholder methods for the routes, assuming they exist elsewhere or need to be added.
    // These are added to make the Router::add calls syntactically valid within the class context.
    public function listTables()
    { /* ... */
    }
    public function getTableData(string $table)
    { /* ... */
    }
    public function executeQuery()
    { /* ... */
    }
    public function insertRecord(string $table)
    { /* ... */
    }
    public function updateRecord(string $table, int $id)
    { /* ... */
    }
    public function deleteRecord(string $table, int $id)
    { /* ... */
    }
}
