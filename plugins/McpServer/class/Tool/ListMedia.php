<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class ListMedia extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'list_media',
            'description' => 'List all media files for a specific site',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $site = $arguments['site'] ?? 'default';
        $rootDir = Env::get('root_dir');
        $assetsDir = ($site === 'default') ? $rootDir . '/my-data/assets' : $rootDir . '/my-data/sites/' . $site . '/assets';

        if (!File::isDirectory($assetsDir)) {
            return $this->resultText("No assets directory found for site '$site'.");
        }

        $files = File::glob($assetsDir . '/*');
        $result = [];
        foreach ($files as $file) {
            $result[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'mtime' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        return $this->resultJson($result);
    }
}
