<?php

namespace FileExplorer\Service;

use GaiaAlpha\Database;
use GaiaAlpha\Env;

class VirtualFsService
{
    private static ?Database $db = null;

    public static function connect(string $dbPath): void
    {
        if (!str_starts_with($dbPath, 'sqlite:')) {
            $dbPath = 'sqlite:' . $dbPath;
        }
        self::$db = new Database($dbPath);
        self::ensureSchema();
    }

    private static function ensureSchema(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS vfs_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            parent_id INTEGER DEFAULT 0,
            name TEXT NOT NULL,
            type TEXT NOT NULL, -- 'file' or 'folder'
            content TEXT,
            size INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_parent_id ON vfs_items(parent_id);";

        $pdo = self::$db->getPdo();
        $pdo->exec($sql);
    }

    public static function listItems(int $parentId): array
    {
        if (!self::$db)
            return [];
        $stmt = self::$db->getPdo()->prepare("SELECT * FROM vfs_items WHERE parent_id = ? ORDER BY type DESC, name ASC");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    }

    public static function getItem(int $id): ?array
    {
        if (!self::$db)
            return null;
        $stmt = self::$db->getPdo()->prepare("SELECT * FROM vfs_items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function createItem(array $data): int
    {
        if (!self::$db)
            return 0;
        $stmt = self::$db->getPdo()->prepare("INSERT INTO vfs_items (parent_id, name, type, content, size, updated_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([
            $data['parent_id'] ?? 0,
            $data['name'],
            $data['type'],
            $data['content'] ?? null,
            isset($data['content']) ? strlen($data['content']) : 0
        ]);
        return (int) self::$db->getPdo()->lastInsertId();
    }

    public static function updateItem(int $id, array $data): bool
    {
        if (!self::$db)
            return false;
        $fields = [];
        $params = [];
        foreach ($data as $key => $val) {
            if (in_array($key, ['name', 'content', 'parent_id'])) {
                $fields[] = "$key = ?";
                $params[] = $val;
            }
        }

        if (isset($data['content'])) {
            $fields[] = "size = ?";
            $params[] = strlen($data['content']);
        }

        if (empty($fields))
            return false;

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $id;

        $sql = "UPDATE vfs_items SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = self::$db->getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function deleteItem(int $id): bool
    {
        if (!self::$db)
            return false;
        $item = self::getItem($id);
        if (!$item)
            return false;

        if ($item['type'] === 'folder') {
            // Recursive delete
            $children = self::listItems($id);
            foreach ($children as $child) {
                self::deleteItem($child['id']);
            }
        }

        $stmt = self::$db->getPdo()->prepare("DELETE FROM vfs_items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function moveItem(int $id, int $newParentId): bool
    {
        return self::updateItem($id, ['parent_id' => $newParentId]);
    }

    public static function getVfsList(): array
    {
        $vfsDir = Env::get('path_data') . '/vfs';
        if (!is_dir($vfsDir)) {
            mkdir($vfsDir, 0755, true);
            return [];
        }
        $files = glob($vfsDir . '/*.sqlite');
        return array_map(function ($f) {
            return [
                'name' => basename($f, '.sqlite'),
                'path' => $f
            ];
        }, $files);
    }
}
