<?php

namespace McpServer\Resource;

use GaiaAlpha\Env;
use GaiaAlpha\File;
use GaiaAlpha\Model\Template;

class TemplateSource extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://templates/{slug}',
            'name' => 'Template Source',
            'description' => 'Read the content or source code of a PHP template',
            'mimeType' => 'text/x-php'
        ];
    }

    public function matches(string $uri): ?array
    {
        if (preg_match('#^cms://templates/([^/]+)$#', $uri, $matches)) {
            if ($matches[1] === 'list')
                return null;
            return $matches;
        }
        return null;
    }

    public function read(string $uri, array $matches): array
    {
        $slug = $matches[1];
        $rootDir = Env::get('root_dir');

        // 1. Try Database
        $tmpl = Template::findBySlug($slug);
        if ($tmpl && isset($tmpl['content'])) {
            return $this->contents($uri, $tmpl['content'], 'text/x-php');
        }

        // 2. Try Filesystem
        $fullPath = $rootDir . '/templates/' . $slug;
        if (!File::exists($fullPath)) {
            if (pathinfo($fullPath, PATHINFO_EXTENSION) !== 'php') {
                $fullPath .= '.php';
            }
        }

        if (File::exists($fullPath)) {
            return $this->contents($uri, File::read($fullPath), 'text/x-php');
        }

        throw new \Exception("Template not found: $slug");
    }
}
