<?php

namespace McpServer\Resource;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Model\Page;

class PageVersions extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://sites/{site}/pages/{slug}/versions',
            'name' => 'Page Versions',
            'description' => 'List historical versions of a page',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string $uri): ?array
    {
        if (preg_match('#^cms://sites/([^/]+)/pages/([^/]+)/versions$#', $uri, $matches)) {
            return [
                'site' => $matches[1],
                'slug' => $matches[2]
            ];
        }
        return null;
    }

    public function read(string $uri, array $matches): array
    {
        $slug = $matches['slug'];
        $page = Page::findBySlug($slug);
        if (!$page) {
            throw new \Exception("Page not found: $slug");
        }

        $pdo = DB::getPdo();
        $stmt = $pdo->prepare("SELECT * FROM cms_page_versions WHERE page_id = ? ORDER BY created_at DESC");
        $stmt->execute([$page['id']]);
        $versions = $stmt->fetchAll();

        return $this->contents($uri, json_encode($versions, JSON_PRETTY_PRINT));
    }
}
