<?php

namespace GaiaAlpha\Model;

class User extends BaseModel
{
    public function findByUsername(string $username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function create(string $username, string $password, int $level = 10)
    {
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, level, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $level]);
        return $this->db->lastInsertId();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT id, username, level, created_at, updated_at FROM users ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function count()
    {
        return $this->db->query("SELECT count(*) FROM users")->fetchColumn();
    }

    public function update(int $id, array $data)
    {
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

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
