<?php

namespace GaiaAlpha\Model;

class Page
{


    public static function findAllByUserId(int $userId, string $cat = 'page')
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT * FROM cms_pages WHERE user_id = ? AND cat = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $cat]);
        return $stmt->fetchAll();
    }

    public static function getLatestPublic(int $limit = 10)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->query("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug, meta_description, meta_keywords
            FROM cms_pages 
            WHERE cat = 'page'
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug, meta_description, meta_keywords
            FROM cms_pages 
            WHERE slug = ? AND cat = 'page'
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public static function create(int $userId, array $data)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("INSERT INTO cms_pages (user_id, title, slug, content, image, cat, tag, template_slug, meta_description, meta_keywords, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([
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
        $sql = "UPDATE cms_pages SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $values[] = $id;
        $values[] = $userId;

        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete(int $id, int $userId)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("DELETE FROM cms_pages WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function findAllCats(int $userId)
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT DISTINCT cat FROM cms_pages WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function count(?string $cat = null)
    {
        if ($cat) {
            $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT count(*) FROM cms_pages WHERE cat = ?");
            $stmt->execute([$cat]);
            return $stmt->fetchColumn();
        }
        return \GaiaAlpha\Controller\DbController::getPdo()->query("SELECT count(*) FROM cms_pages")->fetchColumn();
    }

    public static function getAppDashboard()
    {
        $stmt = \GaiaAlpha\Controller\DbController::getPdo()->prepare("SELECT slug FROM cms_pages WHERE template_slug = 'app' LIMIT 1");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
