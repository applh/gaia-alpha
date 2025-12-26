<?php

namespace GaiaAlpha\Model;

use PDO;

class User
{


    /**
     * Find a user by ID
     * @return object|false
     */
    public static function find($id)
    {
        return DB::fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }

    /**
     * Find a user by username
     * @return array|false 
     */
    public static function findByUsername(string $username)
    {
        return DB::fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public static function create(string $username, string $password, int $level = 10)
    {
        DB::query("INSERT INTO users (username, password_hash, level, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)", [
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $level
        ]);
        return DB::lastInsertId();
    }

    public static function findAll()
    {
        return DB::fetchAll("SELECT id, username, level, created_at, updated_at FROM users ORDER BY id DESC");
    }

    public static function count()
    {
        return DB::fetchColumn("SELECT count(*) FROM users");
    }

    public static function update($id, $data)
    {
        // Use DB::update logic if possible, but User has specific password hashing
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

        return DB::execute($sql, $values) > 0;
    }

    public static function delete($id)
    {
        return DB::execute("DELETE FROM users WHERE id = ?", [$id]) > 0;
    }
}
