<?php

namespace McpServer\Tool;

use GaiaAlpha\Service\ScaffoldingService;
use Exception;

class ScaffoldMcpFeature extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'scaffold_mcp_feature',
            'description' => 'Create a new MCP feature (tool, resource, or prompt) in the McpServer plugin.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'enum' => ['tool', 'resource', 'prompt'],
                        'description' => 'Type of MCP feature to create'
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Name of the class (e.g. MyNewTool). Should be CamelCase.'
                    ]
                ],
                'required' => ['type', 'name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $type = $arguments['type'] ?? null;
        $name = $arguments['name'] ?? null;

        if (!$type || !$name) {
            throw new Exception("Type and Name are required.");
        }

        try {
            $service = new ScaffoldingService();
            switch ($type) {
                case 'tool':
                    $result = $service->createMcpTool($name);
                    break;
                case 'resource':
                    $result = $service->createMcpResource($name);
                    break;
                case 'prompt':
                    $result = $service->createMcpPrompt($name);
                    break;
                default:
                    throw new Exception("Invalid MCP feature type: $type");
            }
            return $this->resultJson($result);
        } catch (Exception $e) {
            throw new Exception("Scaffolding failed: " . $e->getMessage());
        }
    }
}
