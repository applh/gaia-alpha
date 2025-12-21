<?php

namespace MediaLibrary\Service;

use GaiaAlpha\Model\DB;

class MediaLibraryService
{
    /**
     * Get all media files with optional filters
     */
    public static function getAllMedia(int $userId, array $filters = []): array
    {
        $query = "SELECT m.*, GROUP_CONCAT(t.name) as tags 
                  FROM cms_media m
                  LEFT JOIN cms_media_tag_relations r ON m.id = r.media_id
                  LEFT JOIN cms_media_tags t ON r.tag_id = t.id
                  WHERE m.user_id = ?";

        $params = [$userId];

        // Filter by tag
        if (!empty($filters['tag'])) {
            $query .= " AND t.slug = ?";
            $params[] = $filters['tag'];
        }

        // Search by filename or metadata
        if (!empty($filters['search'])) {
            $query .= " AND (m.filename LIKE ? OR m.original_filename LIKE ? OR m.alt_text LIKE ? OR m.caption LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " GROUP BY m.id ORDER BY m.created_at DESC";

        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = (int) $filters['limit'];
        }

        return DB::fetchAll($query, $params);
    }

    /**
     * Get a single media file by ID
     */
    public static function getMediaById(int $id): ?array
    {
        $media = DB::fetch(
            "SELECT * FROM cms_media WHERE id = ?",
            [$id]
        );

        if ($media) {
            // Get associated tags
            $tags = DB::fetchAll(
                "SELECT t.* FROM cms_media_tags t
                 JOIN cms_media_tag_relations r ON t.id = r.tag_id
                 WHERE r.media_id = ?",
                [$id]
            );
            $media['tags'] = $tags;
        }

        return $media ?: null;
    }

    /**
     * Create a new media record
     */
    public static function createMedia(array $data): int
    {
        DB::execute(
            "INSERT INTO cms_media (user_id, filename, original_filename, mime_type, file_size, width, height, alt_text, caption, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $data['user_id'],
                $data['filename'],
                $data['original_filename'] ?? $data['filename'],
                $data['mime_type'],
                $data['file_size'] ?? 0,
                $data['width'] ?? null,
                $data['height'] ?? null,
                $data['alt_text'] ?? '',
                $data['caption'] ?? ''
            ]
        );

        return DB::lastInsertId();
    }

    /**
     * Update media metadata
     */
    public static function updateMedia(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['alt_text'])) {
            $fields[] = 'alt_text = ?';
            $params[] = $data['alt_text'];
        }

        if (isset($data['caption'])) {
            $fields[] = 'caption = ?';
            $params[] = $data['caption'];
        }

        if (isset($data['original_filename'])) {
            $fields[] = 'original_filename = ?';
            $params[] = $data['original_filename'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = datetime('now')";
        $params[] = $id;

        $query = "UPDATE cms_media SET " . implode(', ', $fields) . " WHERE id = ?";
        DB::execute($query, $params);

        return true;
    }

    /**
     * Delete a media file
     */
    public static function deleteMedia(int $id): bool
    {
        // Get media info before deletion
        $media = self::getMediaById($id);
        if (!$media) {
            return false;
        }

        // Delete from database (cascade will handle relations)
        DB::execute("DELETE FROM cms_media WHERE id = ?", [$id]);

        // Delete physical file
        $filePath = \GaiaAlpha\Env::get('path_data') . '/uploads/' . $media['user_id'] . '/' . $media['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return true;
    }

    /**
     * Get all tags
     */
    public static function getAllTags(): array
    {
        return DB::fetchAll(
            "SELECT t.*, COUNT(r.media_id) as media_count 
             FROM cms_media_tags t
             LEFT JOIN cms_media_tag_relations r ON t.id = r.tag_id
             GROUP BY t.id
             ORDER BY t.name ASC"
        );
    }

    /**
     * Create a new tag
     */
    public static function createTag(string $name, string $color = '#6366f1'): int
    {
        $slug = self::slugify($name);

        DB::execute(
            "INSERT INTO cms_media_tags (name, slug, color, created_at) VALUES (?, ?, ?, datetime('now'))",
            [$name, $slug, $color]
        );

        return DB::lastInsertId();
    }

    /**
     * Delete a tag
     */
    public static function deleteTag(int $id): bool
    {
        DB::execute("DELETE FROM cms_media_tags WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Assign tags to a media file
     */
    public static function assignTags(int $mediaId, array $tagIds): bool
    {
        // Remove existing tags
        DB::execute("DELETE FROM cms_media_tag_relations WHERE media_id = ?", [$mediaId]);

        // Add new tags
        foreach ($tagIds as $tagId) {
            DB::execute(
                "INSERT INTO cms_media_tag_relations (media_id, tag_id, created_at) VALUES (?, ?, datetime('now'))",
                [$mediaId, $tagId]
            );
        }

        return true;
    }

    /**
     * Search media files
     */
    public static function searchMedia(string $query, int $userId): array
    {
        return self::getAllMedia($userId, ['search' => $query]);
    }

    /**
     * Get media library statistics
     */
    public static function getStats(int $userId): array
    {
        $totalFiles = DB::fetch(
            "SELECT COUNT(*) as count FROM cms_media WHERE user_id = ?",
            [$userId]
        )['count'] ?? 0;

        $totalSize = DB::fetch(
            "SELECT SUM(file_size) as size FROM cms_media WHERE user_id = ?",
            [$userId]
        )['size'] ?? 0;

        $fileTypes = DB::fetchAll(
            "SELECT mime_type, COUNT(*) as count FROM cms_media WHERE user_id = ? GROUP BY mime_type",
            [$userId]
        );

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => self::formatBytes($totalSize),
            'file_types' => $fileTypes
        ];
    }

    /**
     * Helper: Create slug from string
     */
    private static function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Helper: Format bytes to human-readable size
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
