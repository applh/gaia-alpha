<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Database;
use PDO;

class BaseModel
{


    // Instance DB removed. Static access only.
    protected static $table;
    protected static $fillable = [];

    public static function all()
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $stmt = $db->query("SELECT * FROM $table ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $stmt = $db->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$id]);
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
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $db->lastInsertId();
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
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete($id)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $table = static::$table;
        $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
