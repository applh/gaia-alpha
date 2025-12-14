<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Hook;
use GaiaAlpha\Database;
use PDO;

class DataStore
{


    public static function set(int $userId, string $type, string $key, string $value)
    {
        $sql = "INSERT INTO data_store (user_id, type, key, value, created_at, updated_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id, type, key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP";

        return BaseModel::execute($sql, [$userId, $type, $key, $value]);
    }

    public static function get(int $userId, string $type, string $key)
    {
        $sql = "SELECT value FROM data_store WHERE user_id = ? AND type = ? AND key = ?";
        return BaseModel::fetchColumn($sql, [$userId, $type, $key]);
    }

    public static function getAll(int $userId, string $type)
    {
        $sql = "SELECT \"key\", \"value\" FROM data_store WHERE user_id = ? AND type = ?";
        $results = BaseModel::fetchAll($sql, [$userId, $type]);

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }
}
