<?php

namespace GaiaAlpha\Model;

class Page extends BaseModel
{
    public function findAllByUserId(int $userId, string $cat = 'page')
    {
        $stmt = $this->db->prepare("SELECT * FROM cms_pages WHERE user_id = ? AND cat = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $cat]);
        return $stmt->fetchAll();
    }

    public function getLatestPublic(int $limit = 10)
    {
        // Only public pages should be cat='page' arguably, or maybe 'public' doesnt matter?
        // Let's assume public pages are always cat='page' for now or just all pages?
        // Requirement says "member cms will only list rows with cat 'page'". Public pages probably implies 'page' type content.
        $stmt = $this->db->query("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug
            FROM cms_pages 
            WHERE cat = 'page'
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug)
    {
        $stmt = $this->db->prepare("
            SELECT id, title, slug, content, image, created_at, user_id, template_slug
            FROM cms_pages 
            WHERE slug = ? AND cat = 'page'
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function create(int $userId, array $data)
    {
        $stmt = $this->db->prepare("INSERT INTO cms_pages (user_id, title, slug, content, image, cat, tag, template_slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([
            $userId,
            $data['title'],
            $data['slug'],
            $data['content'] ?? '',
            $data['image'] ?? null,
            $data['cat'] ?? 'page',
            $data['tag'] ?? null,
            $data['template_slug'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data)
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

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE cms_pages SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $values[] = $id;
        $values[] = $userId;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id, int $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM cms_pages WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function findAllCats(int $userId)
    {
        $stmt = $this->db->prepare("SELECT DISTINCT cat FROM cms_pages WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function count(?string $cat = null)
    {
        if ($cat) {
            $stmt = $this->db->prepare("SELECT count(*) FROM cms_pages WHERE cat = ?");
            $stmt->execute([$cat]);
            return $stmt->fetchColumn();
        }
        return $this->db->query("SELECT count(*) FROM cms_pages")->fetchColumn();
    }
}
