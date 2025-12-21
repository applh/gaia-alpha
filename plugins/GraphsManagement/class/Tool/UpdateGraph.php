<?php

namespace GraphsManagement\Tool;

use McpServer\Tool\BaseTool;
use GraphsManagement\Service\GraphService;

class UpdateGraph extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'update_graph',
            'description' => 'Update an existing graph configuration',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'graph_id' => [
                        'type' => 'integer',
                        'description' => 'Graph ID'
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'New graph title'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'New graph description'
                    ],
                    'chart_type' => [
                        'type' => 'string',
                        'description' => 'New chart type'
                    ],
                    'data_source_config' => [
                        'type' => 'object',
                        'description' => 'Updated data source configuration'
                    ],
                    'chart_config' => [
                        'type' => 'object',
                        'description' => 'Updated Chart.js configuration'
                    ],
                    'refresh_interval' => [
                        'type' => 'integer',
                        'description' => 'Updated refresh interval'
                    ],
                    'is_public' => [
                        'type' => 'boolean',
                        'description' => 'Updated public status'
                    ],
                    'site' => [
                        'type' => 'string',
                        'description' => 'Site domain (default: default)'
                    ]
                ],
                'required' => ['graph_id']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $graphId = $arguments['graph_id'] ?? null;

        if (!$graphId) {
            throw new \Exception('graph_id is required');
        }

        unset($arguments['graph_id']);
        unset($arguments['site']);

        try {
            GraphService::updateGraph((int) $graphId, $arguments);
            $graph = GraphService::getGraph((int) $graphId);

            return $this->resultJson($graph);
        } catch (\Exception $e) {
            throw new \Exception('Failed to update graph: ' . $e->getMessage());
        }
    }
}
