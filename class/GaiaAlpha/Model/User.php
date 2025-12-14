<?php

namespace GaiaAlpha\Model;

use PDO;

class User
{


    public static function findByUsername(string $username)
    {
        return BaseModel::fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public static function create(string $username, string $password, int $level = 10)
    {
        BaseModel::query("INSERT INTO users (username, password_hash, level, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)", [
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $level
        ]);
        return \GaiaAlpha\Controller\DbController::getPdo()->lastInsertId();
    }

    public static function findAll()
    {
        return BaseModel::fetchAll("SELECT id, username, level, created_at, updated_at FROM users ORDER BY id DESC");
    }

    public static function count()
    {
        return BaseModel::fetchColumn("SELECT count(*) FROM users");
    }

    public static function update($id, $data)
    {
        // Use BaseModel::update logic if possible, but User has specific password hashing
        $fields = [];
        $values = [];

        if (isset($data['level'])) {
            $fields[] = "level = ?";
            $values[] = (int) $data['level'];
        }

        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $values[] = $id;

        return BaseModel::execute($sql, $values) > 0;
    }

    public static function delete($id)
    {
        return BaseModel::execute("DELETE FROM users WHERE id = ?", [$id]) > 0;
    }
}
