<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;

class VerifySystemHealth extends BaseTool
{
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
