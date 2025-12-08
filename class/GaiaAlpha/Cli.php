<?php

namespace GaiaAlpha;

use Exception;
use PDO;

class Cli
{
    private Database $db;
    private PDO $pdo;
    private Media $media;

    public function __construct(string $dsn, string $mediaPath)
    {
        $this->db = new Database($dsn);
        $this->db->ensureSchema();
        $this->pdo = $this->db->getPdo();
        $this->media = new Media($mediaPath);
    }

    public function run(array $argv): void
    {
        if (count($argv) < 2) {
            $this->showHelp();
            exit(1);
        }

        $command = $argv[1];

        try {
            switch ($command) {
                case 'table:list':
                    $this->handleTableList($argv);
                    break;
                case 'table:insert':
                    $this->handleTableInsert($argv);
                    break;
                case 'table:update':
                    $this->handleTableUpdate($argv);
                    break;
                case 'table:delete':
                    $this->handleTableDelete($argv);
                    break;
                case 'sql':
                    $this->handleSql($argv);
                    break;
                case 'media:stats':
                    $this->handleMediaStats();
                    break;
                case 'media:clear-cache':
                    $this->handleMediaClearCache();
                    break;
                case 'file:write':
                    $this->handleFileWrite($argv);
                    break;
                case 'file:read':
                    $this->handleFileRead($argv);
                    break;
                case 'file:list':
                    $this->handleFileList($argv);
                    break;
                case 'file:delete':
                    $this->handleFileDelete($argv);
                    break;
                case 'file:move':
                    $this->handleFileMove($argv);
                    break;
                case 'help':
                    $this->showHelp();
                    break;
                default:
                    echo "Unknown command: $command\n";
                    $this->showHelp();
                    exit(1);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function showHelp(): void
    {
        echo "Usage: php cli.php <command> [arguments]\n\n";
        echo "Commands:\n";
        echo "  table:list <table>                  List all rows in a table\n";
        echo "  table:insert <table> <json_data>    Insert a row (e.g. '{\"col\":\"val\"}')\n";
        echo "  table:update <table> <id> <json>    Update a row by ID\n";
        echo "  table:delete <table> <id>           Delete a row by ID\n";
        echo "  sql <query>                         Execute a raw SQL query\n";
        echo "  media:stats                         Show storage stats for uploads and cache\n";
        echo "  media:clear-cache                   Clear all cached images\n";
        echo "  file:write <path> <content>         Write content to a file in my-data\n";
        echo "  file:read <path>                    Read content from a file in my-data\n";
        echo "  file:list [path]                    List files in my-data (or subdirectory)\n";
        echo "  file:delete <path>                  Delete a file in my-data\n";
        echo "  file:move <source> <destination>    Move/rename a file in my-data\n";
        echo "  help                                Show this help message\n";
    }

    private function handleTableList(array $args): void
    {
        if (!isset($args[2]))
            die("Missing table name.\n");
        $table = $args[2];
        $stmt = $this->pdo->prepare("SELECT * FROM $table");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    }

    private function handleTableInsert(array $args): void
    {
        if (!isset($args[2]) || !isset($args[3]))
            die("Usage: table:insert <table> <json_data>\n");
        $table = $args[2];
        $data = json_decode($args[3], true);
        if (!$data)
            die("Invalid JSON data.\n");

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        echo "Row inserted. ID: " . $this->pdo->lastInsertId() . "\n";
    }

    private function handleTableUpdate(array $args): void
    {
        if (!isset($args[2]) || !isset($args[3]) || !isset($args[4]))
            die("Usage: table:update <table> <id> <json_data>\n");
        $table = $args[2];
        $id = $args[3];
        $data = json_decode($args[4], true);
        if (!$data)
            die("Invalid JSON data.\n");

        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "$col = ?";
        }
        $setString = implode(', ', $sets);

        $sql = "UPDATE $table SET $setString WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        echo "Row updated.\n";
    }

    private function handleTableDelete(array $args): void
    {
        if (!isset($args[2]) || !isset($args[3]))
            die("Usage: table:delete <table> <id>\n");
        $table = $args[2];
        $id = $args[3];

        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);

        echo "Row deleted.\n";
    }

    private function handleSql(array $args): void
    {
        if (!isset($args[2]))
            die("Missing SQL query.\n");
        $sql = $args[2];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        if (stripos(trim($sql), 'SELECT') === 0) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Query executed. Rows affected: " . $stmt->rowCount() . "\n";
        }
    }

    private function handleMediaStats(): void
    {
        $stats = $this->media->getStats();
        echo "Media Storage Stats:\n";
        echo "--------------------\n";
        echo "Uploads: " . $stats['uploads']['count'] . " files (" . $this->formatBytes($stats['uploads']['size']) . ")\n";
        echo "Cache:   " . $stats['cache']['count'] . " files (" . $this->formatBytes($stats['cache']['size']) . ")\n";
    }

    private function handleMediaClearCache(): void
    {
        $count = $this->media->clearCache();
        echo "Cache cleared. Deleted $count files.\n";
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    // File Management Methods

    private function getDataPath(): string
    {
        return defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : App::$rootDir . '/my-data';
    }

    private function validatePath(string $path): string
    {
        // Remove any directory traversal attempts
        $path = str_replace(['../', '..\\'], '', $path);
        $fullPath = $this->getDataPath() . '/' . ltrim($path, '/');

        // Ensure the path is within my-data directory
        $realDataPath = realpath($this->getDataPath());
        $realFullPath = realpath(dirname($fullPath));

        if ($realFullPath === false || strpos($realFullPath, $realDataPath) !== 0) {
            throw new Exception("Access denied: Path must be within my-data directory");
        }

        return $fullPath;
    }

    private function handleFileWrite(array $args): void
    {
        if (!isset($args[2]) || !isset($args[3])) {
            die("Usage: file:write <path> <content>\n");
        }

        $path = $args[2];
        $content = $args[3];
        $fullPath = $this->validatePath($path);

        // Create directory if it doesn't exist
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);
        echo "File written: $path\n";
    }

    private function handleFileRead(array $args): void
    {
        if (!isset($args[2])) {
            die("Usage: file:read <path>\n");
        }

        $path = $args[2];
        $fullPath = $this->validatePath($path);

        if (!file_exists($fullPath)) {
            die("File not found: $path\n");
        }

        if (!is_file($fullPath)) {
            die("Not a file: $path\n");
        }

        echo file_get_contents($fullPath);
    }

    private function handleFileList(array $args): void
    {
        $subPath = $args[2] ?? '';
        $basePath = $this->getDataPath();
        $fullPath = $subPath ? $this->validatePath($subPath) : $basePath;

        if (!is_dir($fullPath)) {
            die("Not a directory: $subPath\n");
        }

        $items = scandir($fullPath);
        echo "Contents of " . ($subPath ?: '/') . ":\n";
        echo "--------------------\n";

        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $itemPath = $fullPath . '/' . $item;
            $type = is_dir($itemPath) ? 'DIR ' : 'FILE';
            $size = is_file($itemPath) ? $this->formatBytes(filesize($itemPath)) : '';

            echo sprintf("%-5s %-20s %s\n", $type, $item, $size);
        }
    }

    private function handleFileDelete(array $args): void
    {
        if (!isset($args[2])) {
            die("Usage: file:delete <path>\n");
        }

        $path = $args[2];
        $fullPath = $this->validatePath($path);

        if (!file_exists($fullPath)) {
            die("File not found: $path\n");
        }

        if (is_dir($fullPath)) {
            die("Cannot delete directories. Use file:delete on individual files.\n");
        }

        unlink($fullPath);
        echo "File deleted: $path\n";
    }

    private function handleFileMove(array $args): void
    {
        if (!isset($args[2]) || !isset($args[3])) {
            die("Usage: file:move <source> <destination>\n");
        }

        $source = $args[2];
        $destination = $args[3];

        $sourcePath = $this->validatePath($source);
        $destPath = $this->validatePath($destination);

        if (!file_exists($sourcePath)) {
            die("Source file not found: $source\n");
        }

        // Create destination directory if needed
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        rename($sourcePath, $destPath);
        echo "File moved: $source -> $destination\n";
    }
}
