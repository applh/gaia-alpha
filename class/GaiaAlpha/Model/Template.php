<?php

namespace GaiaAlpha\Model;

use PDO;

class Template
{


    public static function findAllByUserId(int $userId)
    {
        return BaseModel::fetchAll("SELECT * FROM cms_templates WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
    }

    public static function findBySlug(string $slug)
    {
        return BaseModel::fetch("SELECT * FROM cms_templates WHERE slug = ?", [$slug]);
    }

    public static function create(int $userId, array $data)
    {
        BaseModel::query("INSERT INTO cms_templates (user_id, title, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)", [
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

        return BaseModel::execute($sql, $values) > 0;
    }

    public static function delete(int $id, int $userId)
    {
        return BaseModel::execute("DELETE FROM cms_templates WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }
}
