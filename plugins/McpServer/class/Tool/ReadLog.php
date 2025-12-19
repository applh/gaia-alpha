<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class ReadLog extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'read_log',
            'description' => 'Read system logs',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'lines' => ['type' => 'integer', 'description' => 'Number of last lines to read', 'default' => 50]
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $lines = $arguments['lines'] ?? 50;
        $logFile = Env::get('root_dir') . '/my-data/logs/system.log';
        if (!File::exists($logFile)) {
            return $this->resultText("Log file not found at $logFile");
        }

        $fileContent = file($logFile);
        $lastLines = array_slice($fileContent, -$lines);
        return $this->resultText(implode("", $lastLines));
    }
}
