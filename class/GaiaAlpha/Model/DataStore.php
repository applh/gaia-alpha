<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Hook;
use GaiaAlpha\Database;
use PDO;

class DataStore
{
    private static array $cache = [];

    public static function set(int $userId, string $type, string $key, string $value)
    {
        $sql = "INSERT INTO data_store (user_id, type, key, value, created_at, updated_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id, type, key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP";

        $result = DB::execute($sql, [$userId, $type, $key, $value]);

        // Clear caches
        unset(self::$cache["$userId:$type"]);
        unset(self::$cache["$userId:$type:$key"]);

        $pathData = \GaiaAlpha\Env::get('path_data');
        $cacheFile = $pathData . "/cache/datastore_{$userId}_{$type}.json";
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        return $result;
    }

    public static function get(int $userId, string $type, string $key)
    {
        $cacheKey = "$userId:$type:$key";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $sql = "SELECT value FROM data_store WHERE user_id = ? AND type = ? AND key = ?";
        $value = DB::fetchColumn($sql, [$userId, $type, $key]);

        self::$cache[$cacheKey] = $value;
        return $value;
    }

    public static function getAll(int $userId, string $type)
    {
        $cacheKey = "$userId:$type";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $pathData = \GaiaAlpha\Env::get('path_data');
        $cacheFile = $pathData . "/cache/datastore_{$userId}_{$type}.json";

        if (file_exists($cacheFile)) {
            $settings = json_decode(file_get_contents($cacheFile), true);
            if (is_array($settings)) {
                self::$cache[$cacheKey] = $settings;
                return $settings;
            }
        }

        $sql = "SELECT \"key\", \"value\" FROM data_store WHERE user_id = ? AND type = ?";
        $results = DB::fetchAll($sql, [$userId, $type]);

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['key']] = $row['value'];
        }

        // Save to file cache
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        file_put_contents($cacheFile, json_encode($settings));

        self::$cache[$cacheKey] = $settings;
        return $settings;
    }
}
