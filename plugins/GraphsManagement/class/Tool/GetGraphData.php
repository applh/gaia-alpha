<?php

namespace GraphsManagement\Tool;

use McpServer\Tool\BaseTool;
use GraphsManagement\Service\GraphService;

class GetGraphData extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'get_graph_data',
            'description' => 'Fetch graph metadata and current data',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'graph_id' => [
                        'type' => 'integer',
                        'description' => 'Graph ID'
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

        try {
            $graph = GraphService::getGraph((int) $graphId);

            if (!$graph) {
                throw new \Exception('Graph not found');
            }

            $data = GraphService::fetchGraphData((int) $graphId);

            return $this->resultJson([
                'graph' => $graph,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch graph data: ' . $e->getMessage());
        }
    }
}
