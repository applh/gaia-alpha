<?php

namespace McpServer\Tool;

use GaiaAlpha\Service\ScaffoldingService;
use Exception;

class ScaffoldController extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'scaffold_controller',
            'description' => 'Create a new controller in a specified plugin.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'plugin' => [
                        'type' => 'string',
                        'description' => 'Target plugin name (e.g. MyPlugin)'
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Controller name (e.g. ItemsController)'
                    ]
                ],
                'required' => ['plugin', 'name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $plugin = $arguments['plugin'] ?? null;
        $name = $arguments['name'] ?? null;

        if (!$plugin || !$name) {
            throw new Exception("Plugin and Controller names are required.");
        }

        try {
            $service = new ScaffoldingService();
            $result = $service->createController($plugin, $name);
            return $this->resultJson($result);
        } catch (Exception $e) {
            throw new Exception("Scaffolding failed: " . $e->getMessage());
        }
    }
}
