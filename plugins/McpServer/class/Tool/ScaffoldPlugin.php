<?php

namespace McpServer\Tool;

use GaiaAlpha\Service\ScaffoldingService;
use Exception;

class ScaffoldPlugin extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'scaffold_plugin',
            'description' => 'Create a new Gaia Alpha plugin structure including basic folders and files.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'The name of the plugin (e.g. MyNewPlugin). Should be CamelCase.'
                    ]
                ],
                'required' => ['name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $name = $arguments['name'] ?? null;
        if (!$name) {
            throw new Exception("Plugin name is required.");
        }

        try {
            $service = new ScaffoldingService();
            $result = $service->createPlugin($name);
            return $this->resultJson($result);
        } catch (Exception $e) {
            throw new Exception("Scaffolding failed: " . $e->getMessage());
        }
    }
}
