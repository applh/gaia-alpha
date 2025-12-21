<?php

namespace GraphsManagement\Tool;

use McpServer\Tool\BaseTool;
use GraphsManagement\Service\GraphService;
use GaiaAlpha\Session;

class ListGraphs extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'list_graphs',
            'description' => 'List all graphs with optional filtering by chart type or search query',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => [
                        'type' => 'string',
                        'description' => 'Site domain (default: default)'
                    ],
                    'chart_type' => [
                        'type' => 'string',
                        'description' => 'Filter by chart type (line, bar, pie, area, scatter, doughnut)'
                    ],
                    'search' => [
                        'type' => 'string',
                        'description' => 'Search in title and description'
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of results'
                    ]
                ],
                'required' => []
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $userId = Session::id();

        $filters = [];
        if (!empty($arguments['chart_type'])) {
            $filters['chart_type'] = $arguments['chart_type'];
        }
        if (!empty($arguments['search'])) {
            $filters['search'] = $arguments['search'];
        }
        if (!empty($arguments['limit'])) {
            $filters['limit'] = (int) $arguments['limit'];
        }

        $graphs = GraphService::listGraphs($userId, $filters);

        return $this->resultJson($graphs);
    }
}
