<?php

namespace McpServer\Tool;

use GaiaAlpha\SiteManager;

class ListSites extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'list_sites',
            'description' => 'List all managed sites',
            'inputSchema' => ['type' => 'object', 'properties' => (object) []]
        ];
    }

    public function execute(array $arguments): array
    {
        $sites = SiteManager::getAllSites();
        return $this->resultJson($sites);
    }
}
