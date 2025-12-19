<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\Page;

class BulkImportPages extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'bulk_import_pages',
            'description' => 'Import multiple pages from JSON or CSV',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)'],
                    'format' => ['type' => 'string', 'enum' => ['json', 'csv'], 'description' => 'Data format'],
                    'data' => ['type' => 'string', 'description' => 'The data string to import (JSON array of objects or CSV)']
                ],
                'required' => ['format', 'data']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $format = $arguments['format'];
        $data = $arguments['data'];
        $userId = 1; // Default to admin for MCP operations

        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        $pages = [];
        if ($format === 'json') {
            $pages = json_decode($data, true);
            if (!is_array($pages)) {
                throw new \Exception("Invalid JSON data. Expected an array of page objects.");
            }
        } elseif ($format === 'csv') {
            $lines = explode("\n", trim($data));
            if (empty($lines)) {
                throw new \Exception("Empty CSV data.");
            }
            $header = str_getcsv(array_shift($lines));
            foreach ($lines as $line) {
                if (empty(trim($line)))
                    continue;
                $row = str_getcsv($line);
                if (count($row) === count($header)) {
                    $pages[] = array_combine($header, $row);
                } else {
                    $results['errors'][] = "CSV row column count mismatch. Expected " . count($header) . " columns.";
                }
            }
        }

        foreach ($pages as $pageData) {
            $slug = $pageData['slug'] ?? null;
            if (!$slug) {
                $results['errors'][] = "Missing slug for page: " . ($pageData['title'] ?? 'unknown');
                continue;
            }

            try {
                $existing = Page::findBySlug($slug);
                if ($existing) {
                    // Save current state as version before update
                    $pdo = \GaiaAlpha\Model\DB::getPdo();
                    $stmt = $pdo->prepare("INSERT INTO cms_page_versions (page_id, title, content, user_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $existing['id'],
                        $existing['title'],
                        $existing['content'],
                        $existing['user_id']
                    ]);

                    Page::update($existing['id'], $userId, $pageData);
                    $results['updated']++;
                } else {
                    Page::create($userId, $pageData);
                    $results['created']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Error processing '$slug': " . $e->getMessage();
            }
        }

        return $this->resultJson($results);
    }
}
