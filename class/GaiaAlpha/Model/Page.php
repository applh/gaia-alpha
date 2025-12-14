<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Hook;
use PDO;

class Page
{


    public static function findAllByUserId(int $userId, string $cat = 'page')
    {
        return BaseModel::fetchAll("SELECT * FROM cms_pages WHERE user_id = ? AND cat = ? ORDER BY created_at DESC", [$userId, $cat]);
    }

    public static function getLatestPublic(int $limit = 10)
    {
        return BaseModel::fetchAll("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug, meta_description, meta_keywords
            FROM cms_pages 
            WHERE cat = 'page'
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
    }

    public static function findBySlug(string $slug)
    {
        return BaseModel::fetch("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug, meta_description, meta_keywords
            FROM cms_pages 
            WHERE slug = ? AND cat = 'page'
        ", [$slug]);
    }

    public static function create(int $userId, array $data)
    {
        $sql = "INSERT INTO cms_pages (user_id, title, slug, content, image, cat, tag, template_slug, meta_description, meta_keywords, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $params = [
            $userId,
            $data['title'],
            $data['slug'],
            $data['content'] ?? '',
            $data['image'] ?? null,
            $data['cat'] ?? 'page',
            $data['tag'] ?? null,
            $data['template_slug'] ?? null,
            $data['meta_description'] ?? null,
            $data['meta_keywords'] ?? null
        ];
        BaseModel::query($sql, $params);
        return BaseModel::lastInsertId();
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
        if (isset($data['image'])) {
            $fields[] = "image = ?";
            $values[] = $data['image'];
        }
        if (isset($data['cat'])) {
            $fields[] = "cat = ?";
            $values[] = $data['cat'];
        }
        if (isset($data['tag'])) {
            $fields[] = "tag = ?";
            $values[] = $data['tag'];
        }
        if (isset($data['template_slug'])) {
            $fields[] = "template_slug = ?";
            $values[] = $data['template_slug'];
        }
        if (isset($data['meta_description'])) {
            $fields[] = "meta_description = ?";
            $values[] = $data['meta_description'];
        }
        if (isset($data['meta_keywords'])) {
            $fields[] = "meta_keywords = ?";
            $values[] = $data['meta_keywords'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $id;
        $values[] = $userId;

        $sql = "UPDATE cms_pages SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";

        return BaseModel::execute($sql, $values) > 0;
    }

    public static function delete(int $id, int $userId)
    {
        return BaseModel::execute("DELETE FROM cms_pages WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }

    public static function findAllCats(int $userId)
    {
        return BaseModel::fetchAll("SELECT DISTINCT cat FROM cms_pages WHERE user_id = ?", [$userId], PDO::FETCH_COLUMN);
    }

    public static function count(?string $cat = null)
    {
        if ($cat) {
            return BaseModel::fetchColumn("SELECT count(*) FROM cms_pages WHERE cat = ?", [$cat]);
        }
        return BaseModel::fetchColumn("SELECT count(*) FROM cms_pages");
    }

    public static function getAppDashboard()
    {
        return BaseModel::fetchColumn("SELECT slug FROM cms_pages WHERE template_slug = 'app' LIMIT 1");
    }
}
