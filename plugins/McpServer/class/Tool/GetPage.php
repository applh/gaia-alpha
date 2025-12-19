<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\Page;

class GetPage extends BaseTool
{
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
