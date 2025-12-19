<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\DB;

class ListPages extends BaseTool
{
    public function execute(array $arguments): array
    {
        $pages = DB::fetchAll("SELECT id, title, slug, cat, created_at FROM cms_pages WHERE cat = 'page' ORDER BY created_at DESC");
        return $this->resultJson($pages);
    }
}
