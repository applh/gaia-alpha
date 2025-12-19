<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\Page;

class UpsertPage extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'upsert_page',
            'description' => 'Create or update a page',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'content' => ['type' => 'string'],
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ],
                'required' => ['title', 'slug', 'content']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $slug = $arguments['slug'] ?? null;
        if (!$slug) {
            throw new \Exception("Slug is required.");
        }

        $existing = Page::findBySlug($slug);
        $userId = 1; // MCP default user

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

            Page::update($existing['id'], $userId, $arguments);
            return $this->resultText("Page '$slug' updated and current version archived.");
        } else {
            Page::create($userId, $arguments);
            return $this->resultText("Page '$slug' created.");
        }
    }
}
