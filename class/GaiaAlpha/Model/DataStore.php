<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Database;
use PDO;

class DataStore
{


    public static function set(int $userId, string $type, string $key, string $value)
    {
        $sql = "INSERT INTO data_store (user_id, type, key, value, updated_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id, type, key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP";

        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare($sql);
        return $stmt->execute([$userId, $type, $key, $value]);
    }

    public static function get(int $userId, string $type, string $key)
    {
        $sql = "SELECT value FROM data_store WHERE user_id = ? AND type = ? AND key = ?";
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare($sql);
        $stmt->execute([$userId, $type, $key]);
        return $stmt->fetchColumn();
    }

    public static function getAll(int $userId, string $type)
    {
        $sql = "SELECT key, value FROM data_store WHERE user_id = ? AND type = ?";
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare($sql);
        $stmt->execute([$userId, $type]);

        $results = $stmt->fetchAll();
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }
}
