<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Hook;
use PDO;

class Page
{
    private static array $cache = [];

    public static function findAllByUserId(int $userId, string $cat = 'page')
    {
        return DB::fetchAll("
            SELECT * 
            FROM cms_pages 
            WHERE user_id = ? AND cat = ? 
            ORDER BY created_at DESC
        ", [$userId, $cat]);
    }

    public static function getLatestPublic(int $limit = 10)
    {
        return DB::fetchAll("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug, meta_description, meta_keywords, content_format, canonical_url, schema_type, schema_data
            FROM cms_pages 
            WHERE cat = 'page'
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
    }

    public static function findBySlug(string $slug)
    {
        if (isset(self::$cache[$slug])) {
            return self::$cache[$slug];
        }

        $page = DB::fetch("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug, meta_description, meta_keywords, content_format, canonical_url, schema_type, schema_data
            FROM cms_pages 
            WHERE slug = ? AND cat = 'page'
        ", [$slug]);

        self::$cache[$slug] = $page;
        return $page;
    }

    public static function create(int $userId, array $data)
    {
        $sql = "INSERT INTO cms_pages (user_id, title, slug, content, image, cat, tag, template_slug, meta_description, meta_keywords, content_format, canonical_url, schema_type, schema_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
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
            $data['meta_keywords'] ?? null,
            $data['content_format'] ?? 'html',
            $data['canonical_url'] ?? null,
            $data['schema_type'] ?? null,
            $data['schema_data'] ?? null
        ];
        DB::query($sql, $params);
        return DB::lastInsertId();
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
        if (isset($data['content_format'])) {
            $fields[] = "content_format = ?";
            $values[] = $data['content_format'];
        }
        if (isset($data['canonical_url'])) {
            $fields[] = "canonical_url = ?";
            $values[] = $data['canonical_url'];
        }
        if (isset($data['schema_type'])) {
            $fields[] = "schema_type = ?";
            $values[] = $data['schema_type'];
        }
        if (isset($data['schema_data'])) {
            $fields[] = "schema_data = ?";
            $values[] = $data['schema_data'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $id;
        $values[] = $userId;

        $sql = "UPDATE cms_pages SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";

        return DB::execute($sql, $values) > 0;
    }

    public static function delete(int $id, int $userId)
    {
        return DB::execute("DELETE FROM cms_pages WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }

    public static function findAllCats(int $userId)
    {
        return DB::fetchAll("SELECT DISTINCT cat FROM cms_pages WHERE user_id = ?", [$userId], PDO::FETCH_COLUMN);
    }

    public static function count(?string $cat = null)
    {
        if ($cat) {
            return DB::fetchColumn("SELECT count(*) FROM cms_pages WHERE cat = ?", [$cat]);
        }
        return DB::fetchColumn("SELECT count(*) FROM cms_pages");
    }

    public static function getAppDashboard()
    {
        return DB::fetchColumn("SELECT slug FROM cms_pages WHERE template_slug = 'app' LIMIT 1");
    }
}
