<?php

namespace McpServer\Resource;

use GaiaAlpha\SiteManager;

class SitesList extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://sites/list',
            'name' => 'All Sites',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string $uri): ?array
    {
        return $uri === 'cms://sites/list' ? [] : null;
    }

    public function read(string $uri, array $matches): array
    {
        $sites = SiteManager::getAllSites();
        return $this->contents($uri, json_encode($sites, JSON_PRETTY_PRINT));
    }
}
