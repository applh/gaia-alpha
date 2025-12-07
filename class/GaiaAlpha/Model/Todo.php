<?php

namespace GaiaAlpha\Model;

class Todo extends BaseModel
{
    public function findAllByUserId(int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $title)
    {
        $stmt = $this->db->prepare("INSERT INTO todos (user_id, title, created_at, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$userId, $title]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, bool $completed)
    {
        $stmt = $this->db->prepare("UPDATE todos SET completed = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        return $stmt->execute([$completed ? 1 : 0, $id, $userId]);
    }

    public function delete(int $id, int $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function count()
    {
        return $this->db->query("SELECT count(*) FROM todos")->fetchColumn();
    }
}
