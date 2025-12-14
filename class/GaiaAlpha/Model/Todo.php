<?php

namespace GaiaAlpha\Model;

use PDO;

class Todo
{


    public static function findAllByUserId(int $userId)
    {
        // Sort by position ASC, then ID ASC for consistent ordering
        return BaseModel::fetchAll("SELECT * FROM todos WHERE user_id = ? ORDER BY parent_id IS NULL DESC, parent_id ASC, position ASC, id ASC", [$userId]);
    }

    public static function findByLabel(int $userId, string $label)
    {
        return BaseModel::fetchAll("SELECT * FROM todos WHERE user_id = ? AND labels LIKE ? ORDER BY position ASC, id ASC", [$userId, "%$label%"]);
    }

    public static function findChildren(int $parentId, int $userId)
    {
        return BaseModel::fetchAll("SELECT * FROM todos WHERE parent_id = ? AND user_id = ? ORDER BY position ASC, id ASC", [$parentId, $userId]);
    }

    public static function find(int $id, int $userId)
    {
        return BaseModel::fetch("SELECT * FROM todos WHERE id = ? AND user_id = ?", [$id, $userId]);
    }

    public static function create(int $userId, string $title, ?int $parentId = null, ?string $labels = null, ?string $startDate = null, ?string $endDate = null, ?string $color = null)
    {
        // Calculate next position
        $sql = "SELECT MAX(position) FROM todos WHERE user_id = ? AND parent_id " . ($parentId === null ? "IS NULL" : "= ?");
        $params = [$userId];
        if ($parentId !== null)
            $params[] = $parentId;

        $maxPos = BaseModel::fetchColumn($sql, $params);
        $position = ($maxPos !== false && $maxPos !== null) ? $maxPos + 1024 : 1024; // Use large gaps for easier reordering

        BaseModel::query(
            "INSERT INTO todos (user_id, title, parent_id, labels, start_date, end_date, color, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [$userId, $title, $parentId, $labels, $startDate, $endDate, $color, $position]
        );

        return \GaiaAlpha\Controller\DbController::getPdo()->lastInsertId();
    }

    public static function updatePosition(int $id, int $userId, ?int $parentId, float $position)
    {
        return BaseModel::execute("UPDATE todos SET parent_id = ?, position = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?", [$parentId, $position, $id, $userId]) > 0;
    }

    public static function update(int $id, int $userId, array $data)
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

        if (isset($data['start_date'])) {
            $fields[] = 'start_date = ?';
            $values[] = $data['start_date'];
        }

        if (isset($data['end_date'])) {
            $fields[] = 'end_date = ?';
            $values[] = $data['end_date'];
        }

        if (isset($data['color'])) {
            $fields[] = 'color = ?';
            $values[] = $data['color'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';
        $values[] = $id;
        $values[] = $userId;

        $sql = "UPDATE todos SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        return BaseModel::execute($sql, $values) > 0;
    }

    public static function delete(int $id, int $userId)
    {
        // First, unlink children (set their parent_id to NULL)
        BaseModel::execute("UPDATE todos SET parent_id = NULL WHERE parent_id = ? AND user_id = ?", [$id, $userId]);

        // Then delete the todo
        return BaseModel::execute("DELETE FROM todos WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }

    public static function count()
    {
        return BaseModel::fetchColumn("SELECT count(*) FROM todos");
    }
}


