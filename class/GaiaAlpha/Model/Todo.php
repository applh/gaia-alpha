<?php

namespace GaiaAlpha\Model;

class Todo
{


    public static function findAllByUserId(int $userId)
    {
        // Sort by position ASC, then ID ASC for consistent ordering
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY parent_id IS NULL DESC, parent_id ASC, position ASC, id ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findByLabel(int $userId, string $label)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM todos WHERE user_id = ? AND labels LIKE ? ORDER BY position ASC, id ASC");
        $stmt->execute([$userId, "%$label%"]);
        return $stmt->fetchAll();
    }

    public static function findChildren(int $parentId, int $userId)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM todos WHERE parent_id = ? AND user_id = ? ORDER BY position ASC, id ASC");
        $stmt->execute([$parentId, $userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id, int $userId)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch();
    }

    public static function create(int $userId, string $title, ?int $parentId = null, ?string $labels = null, ?string $startDate = null, ?string $endDate = null, ?string $color = null)
    {
        // Calculate next position
        $pdo = \GaiaAlpha\Controller\DbController::getPdo();
        $sql = "SELECT MAX(position) FROM todos WHERE user_id = ? AND parent_id " . ($parentId === null ? "IS NULL" : "= ?");
        $params = [$userId];
        if ($parentId !== null)
            $params[] = $parentId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $maxPos = $stmt->fetchColumn();
        $position = ($maxPos !== false && $maxPos !== null) ? $maxPos + 1024 : 1024; // Use large gaps for easier reordering

        $stmt = $pdo->prepare("INSERT INTO todos (user_id, title, parent_id, labels, start_date, end_date, color, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$userId, $title, $parentId, $labels, $startDate, $endDate, $color, $position]);
        return $pdo->lastInsertId();
    }

    public static function updatePosition(int $id, int $userId, ?int $parentId, float $position)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("UPDATE todos SET parent_id = ?, position = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        return $stmt->execute([$parentId, $position, $id, $userId]);
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
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete(int $id, int $userId)
    {
        $pdo = \GaiaAlpha\Controller\DbController::getPdo();
        // First, unlink children (set their parent_id to NULL)
        $stmt = $pdo->prepare("UPDATE todos SET parent_id = NULL WHERE parent_id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        // Then delete the todo
        $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function count()
    {
        return \GaiaAlpha\Controller\DbController::getPdo()->query("SELECT count(*) FROM todos")->fetchColumn();
    }
}


