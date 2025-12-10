<?php

namespace GaiaAlpha\Model;

class Template
{


    public static function findAllByUserId(int $userId)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM cms_templates WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM cms_templates WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public static function create(int $userId, array $data)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("INSERT INTO cms_templates (user_id, title, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([
            $userId,
            $data['title'],
            $data['slug'],
            $data['content'] ?? ''
        ]);
        return \GaiaAlpha\Controller\DbController::getPdo()->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data)
    {
        $fields = [];
        $values = [];

        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $values[] = $data['title'];
        }
        if (isset($data['content'])) {
            $fields[] = "content = ?";
            $values[] = $data['content'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE cms_templates SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $values[] = $id;
        $values[] = $userId;

        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete(int $id, int $userId)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("DELETE FROM cms_templates WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
