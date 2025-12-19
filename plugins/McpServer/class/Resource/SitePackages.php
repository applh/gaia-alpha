<?php

namespace McpServer\Resource;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class SitePackages extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://sites/packages',
            'name' => 'Site Packages',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string $uri): ?array
    {
        return $uri === 'cms://sites/packages' ? [] : null;
    }

    public function read(string $uri, array $matches): array
    {
        $packagesDir = Env::get('root_dir') . '/docs/examples';
        $packages = [];
        if (File::isDirectory($packagesDir)) {
            $dirs = File::glob($packagesDir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $packages[] = [
                    'name' => basename($dir),
                    'path' => str_replace(Env::get('root_dir') . '/', '', $dir)
                ];
            }
        }
        return $this->contents($uri, json_encode($packages, JSON_PRETTY_PRINT));
    }
}
