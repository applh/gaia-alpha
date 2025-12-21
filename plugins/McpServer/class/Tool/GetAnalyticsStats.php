<?php

namespace McpServer\Tool;

use Analytics\Service\AnalyticsService;

class GetAnalyticsStats extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'get_analytics_stats',
            'description' => 'Retrieve site analytics statistics including total visits, unique visitors, top pages, and historical data for the last 30 days.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => [
                        'type' => 'string',
                        'description' => 'The site domain to query (default: "default")'
                    ],
                    'days' => [
                        'type' => 'integer',
                        'description' => 'Number of days of history to retrieve (default: 30)',
                        'default' => 30
                    ]
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        if (!class_exists(AnalyticsService::class)) {
            return $this->resultText("Analytics plugin is not installed or enabled.");
        }

        $days = $arguments['days'] ?? 30;
        $service = AnalyticsService::getInstance();
        $stats = $service->getStats($days);

        return $this->resultJson($stats);
    }
}
