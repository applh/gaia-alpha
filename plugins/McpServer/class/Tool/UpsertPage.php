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
        // We use first user (admin) for MCP operations by default for now
        $userId = 1;
        if ($existing) {
            Page::update($existing['id'], $userId, $arguments);
            return $this->resultText("Page '$slug' updated.");
        } else {
            Page::create($userId, $arguments);
            return $this->resultText("Page '$slug' created.");
        }
    }
}
