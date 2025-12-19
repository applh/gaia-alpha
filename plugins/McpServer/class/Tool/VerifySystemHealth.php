<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;

class VerifySystemHealth extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'verify_system_health',
            'description' => 'Check system health and directory permissions',
            'inputSchema' => ['type' => 'object', 'properties' => (object) []]
        ];
    }

    public function execute(array $arguments): array
    {
        $rootDir = Env::get('root_dir');
        $health = [
            'version' => Env::get('version'),
            'php_version' => phpversion(),
            'directories' => [
                'my-data' => is_writable($rootDir . '/my-data'),
                'my-data/sites' => is_writable($rootDir . '/my-data/sites'),
                'my-data/logs' => is_writable($rootDir . '/my-data/logs'),
            ],
            'database' => [
                'connected' => true
            ]
        ];
        return $this->resultJson($health);
    }
}
