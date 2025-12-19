<?php

namespace McpServer\Tool;

use GaiaAlpha\SiteManager;

class ListSites extends BaseTool
{
    public function execute(array $arguments): array
    {
        $sites = SiteManager::getAllSites();
        return $this->resultJson($sites);
    }
}
