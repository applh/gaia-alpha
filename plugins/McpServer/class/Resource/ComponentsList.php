<?php

namespace McpServer\Resource;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class ComponentsList extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://components/list',
            'name' => 'All JS Components',
            'description' => 'List all available Vue 3 components (core and custom)',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string $uri): ?array
    {
        return $uri === 'cms://components/list' ? [] : null;
    }

    public function read(string $uri, array $matches): array
    {
        $rootDir = Env::get('root_dir');
        $components = [];

        // 1. Scan Core Components
        $coreDir = $rootDir . '/resources/js/components';
        if (File::isDirectory($coreDir)) {
            $this->scanDirectory($coreDir, 'core', $coreDir, $components);
        }

        // 2. Scan Custom Components
        $customDir = $rootDir . '/my-data/components/custom';
        if (File::isDirectory($customDir)) {
            $this->scanDirectory($customDir, 'custom', $customDir, $components);
        }

        return $this->contents($uri, json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function scanDirectory($dir, $type, $baseDir, &$results)
    {
        $files = File::glob($dir . '/*');
        foreach ($files as $file) {
            if (File::isDirectory($file)) {
                $this->scanDirectory($file, $type, $baseDir, $results);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'js') {
                $relativePath = str_replace($baseDir . '/', '', $file);
                $results[] = [
                    'name' => basename($file, '.js'),
                    'path' => $relativePath,
                    'type' => $type,
                    'uri' => 'cms://components/' . ($type === 'custom' ? 'custom/' : '') . $relativePath
                ];
            }
        }
    }
}
