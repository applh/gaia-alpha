<?php

namespace GraphsManagement\Tool;

use McpServer\Tool\BaseTool;
use GraphsManagement\Service\GraphService;

class CreateGraph extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'create_graph',
            'description' => 'Create a new graph with specified configuration',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'description' => 'Graph title'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Graph description'
                    ],
                    'chart_type' => [
                        'type' => 'string',
                        'description' => 'Chart type: line, bar, pie, area, scatter, doughnut, radar, polarArea'
                    ],
                    'data_source_type' => [
                        'type' => 'string',
                        'description' => 'Data source type: manual, database, api'
                    ],
                    'data_source_config' => [
                        'type' => 'object',
                        'description' => 'Data source configuration (structure depends on data_source_type)'
                    ],
                    'chart_config' => [
                        'type' => 'object',
                        'description' => 'Chart.js configuration options'
                    ],
                    'refresh_interval' => [
                        'type' => 'integer',
                        'description' => 'Auto-refresh interval in seconds (0 = disabled)'
                    ],
                    'is_public' => [
                        'type' => 'boolean',
                        'description' => 'Whether graph can be embedded publicly'
                    ],
                    'site' => [
                        'type' => 'string',
                        'description' => 'Site domain (default: default)'
                    ]
                ],
                'required' => ['title', 'chart_type', 'data_source_type', 'data_source_config']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $graph = GraphService::createGraph($arguments);
            return $this->resultJson($graph);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create graph: ' . $e->getMessage());
        }
    }
}
