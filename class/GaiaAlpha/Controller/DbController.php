<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;
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
}
