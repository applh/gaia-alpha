<?php

namespace GaiaAlpha\Model;

class User
{


    public static function findByUsername(string $username)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public static function create(string $username, string $password, int $level = 10)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, level, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $level]);
        return $db->lastInsertId();
    }

    public static function findAll()
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->query("SELECT id, username, level, created_at, updated_at FROM users ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public static function count()
    {
        return \GaiaAlpha\Controller\DbController::getPdo()->query("SELECT count(*) FROM users")->fetchColumn();
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

        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete($id)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
