<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class SearchPlugins extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'search_plugins',
            'description' => 'Search for available and installed plugins',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Search term']
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $query = strtolower($arguments['query'] ?? '');
        $rootDir = Env::get('root_dir');
        $pluginsDir = $rootDir . '/plugins';

        $installed = [];
        $dirs = glob($pluginsDir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $name = basename($dir);
            $pluginJsonPath = $dir . '/plugin.json';
            $description = '';
            if (file_exists($pluginJsonPath)) {
                $info = json_decode(file_get_contents($pluginJsonPath), true);
                $description = $info['description'] ?? '';
            }

            if (empty($query) || strpos(strtolower($name), $query) !== false || strpos(strtolower($description), $query) !== false) {
                $installed[] = [
                    'name' => $name,
                    'description' => $description,
                    'status' => 'installed'
                ];
            }
        }

        // Simulated marketplace plugins
        $marketplace = [
            ['name' => 'Analytics', 'description' => 'Track visitor behavior', 'version' => '2.0.1'],
            ['name' => 'Ecommerce', 'description' => 'Full-featured online store', 'version' => '1.5.0'],
            ['name' => 'SitemapPro', 'description' => 'Advanced XML sitemap generator', 'version' => '1.0.3'],
            ['name' => 'SecurityShield', 'description' => 'Brute force protection and firewall', 'version' => '2.1.0']
        ];

        $available = [];
        foreach ($marketplace as $plugin) {
            if (empty($query) || strpos(strtolower($plugin['name']), $query) !== false || strpos(strtolower($plugin['description']), $query) !== false) {
                $available[] = array_merge($plugin, ['status' => 'available']);
            }
        }

        return $this->resultJson([
            'installed' => $installed,
            'available' => $available
        ]);
    }
}
