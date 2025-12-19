<?php

namespace McpServer\Resource;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class ComponentSource extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://components/{path}',
            'name' => 'JS Component Source',
            'description' => 'Read the source code of a specific JS component',
            'mimeType' => 'text/javascript'
        ];
    }

    public function matches(string $uri): ?array
    {
        if (preg_match('#^cms://components/(.+)$#', $uri, $matches)) {
            if ($matches[1] === 'list')
                return null; // Avoid collision with list resource
            return $matches;
        }
        return null;
    }

    public function read(string $uri, array $matches): array
    {
        $path = $matches[1];
        $rootDir = Env::get('root_dir');

        // Try Custom first
        if (strpos($path, 'custom/') === 0) {
            $realPath = str_replace('custom/', '', $path);
            $fullPath = $rootDir . '/my-data/components/custom/' . $realPath;
        } else {
            // Try Core
            $fullPath = $rootDir . '/resources/js/components/' . $path;
        }

        if (!File::exists($fullPath)) {
            // Check if user forgot .js extension
            if (pathinfo($fullPath, PATHINFO_EXTENSION) !== 'js') {
                $fullPath .= '.js';
            }
        }

        if (!File::exists($fullPath)) {
            throw new \Exception("Component not found: $path (tried $fullPath)");
        }

        return $this->contents($uri, File::read($fullPath), 'text/javascript');
    }
}
