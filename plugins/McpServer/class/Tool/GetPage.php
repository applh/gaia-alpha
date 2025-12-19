<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\Page;

class GetPage extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'get_page',
            'description' => 'Get full content of a page by its slug',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'slug' => ['type' => 'string', 'description' => 'Page slug'],
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ],
                'required' => ['slug']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $slug = $arguments['slug'] ?? null;
        if (!$slug) {
            throw new \Exception("Slug is required.");
        }
        $page = Page::findBySlug($slug);
        if (!$page) {
            throw new \Exception("Page not found: $slug");
        }
        return $this->resultJson($page);
    }
}
