<?php

namespace McpServer\Resource;

use GaiaAlpha\Env;
use GaiaAlpha\File;
use GaiaAlpha\Model\DB;

class TemplatesList extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://templates/list',
            'name' => 'All Templates',
            'description' => 'List all available PHP templates (database and filesystem)',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string $uri): ?array
    {
        return $uri === 'cms://templates/list' ? [] : null;
    }

    public function read(string $uri, array $matches): array
    {
        $rootDir = Env::get('root_dir');
        $templates = [];

        // 1. Scan Filesystem Templates
        $tmplDir = $rootDir . '/templates';
        if (File::isDirectory($tmplDir)) {
            $files = File::glob($tmplDir . '/*.php');
            foreach ($files as $file) {
                $base = basename($file);
                $templates[] = [
                    'name' => $base,
                    'slug' => str_replace('.php', '', $base),
                    'type' => 'filesystem',
                    'uri' => 'cms://templates/' . str_replace('.php', '', $base)
                ];
            }
        }

        // 2. Scan Database Templates
        try {
            $dbTemplates = DB::fetchAll("SELECT title, slug FROM cms_templates");
            foreach ($dbTemplates as $tmpl) {
                $templates[] = [
                    'name' => $tmpl['title'],
                    'slug' => $tmpl['slug'],
                    'type' => 'database',
                    'uri' => 'cms://templates/' . $tmpl['slug']
                ];
            }
        } catch (\Exception $e) {
            // Table might not exist yet if fresh install
        }

        return $this->contents($uri, json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
