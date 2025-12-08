<?php

namespace GaiaAlpha\Model;

class Todo extends BaseModel
{
    public function findAllByUserId(int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY parent_id IS NULL DESC, parent_id ASC, id ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findByLabel(int $userId, string $label)
    {
        $stmt = $this->db->prepare("SELECT * FROM todos WHERE user_id = ? AND labels LIKE ? ORDER BY id DESC");
        $stmt->execute([$userId, "%$label%"]);
        return $stmt->fetchAll();
    }

    public function findChildren(int $parentId, int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM todos WHERE parent_id = ? AND user_id = ? ORDER BY id ASC");
        $stmt->execute([$parentId, $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch();
    }

    public function create(int $userId, string $title, ?int $parentId = null, ?string $labels = null)
    {
        $stmt = $this->db->prepare("INSERT INTO todos (user_id, title, parent_id, labels, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$userId, $title, $parentId, $labels]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data)
    {
        $fields = [];
        $values = [];

        if (isset($data['completed'])) {
            $fields[] = 'completed = ?';
            $values[] = $data['completed'] ? 1 : 0;
        }

        if (isset($data['title'])) {
            $fields[] = 'title = ?';
            $values[] = $data['title'];
        }

        if (isset($data['parent_id'])) {
            $fields[] = 'parent_id = ?';
            $values[] = $data['parent_id'];
        }

        if (isset($data['labels'])) {
            $fields[] = 'labels = ?';
            $values[] = $data['labels'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';
        $values[] = $id;
        $values[] = $userId;

        $sql = "UPDATE todos SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id, int $userId)
    {
        // First, unlink children (set their parent_id to NULL)
        $stmt = $this->db->prepare("UPDATE todos SET parent_id = NULL WHERE parent_id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        // Then delete the todo
        $stmt = $this->db->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function count()
    {
        return $this->db->query("SELECT count(*) FROM todos")->fetchColumn();
    }
}
