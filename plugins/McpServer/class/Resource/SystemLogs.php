<?php

namespace McpServer\Resource;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class SystemLogs extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://system/logs',
            'name' => 'System Logs',
            'mimeType' => 'text/plain'
        ];
    }

    public function matches(string $uri): ?array
    {
        return $uri === 'cms://system/logs' ? [] : null;
    }

    public function read(string $uri, array $matches): array
    {
        $logFile = Env::get('root_dir') . '/my-data/logs/system.log';
        if (!File::exists($logFile)) {
            return $this->contents($uri, 'Log file not found.', 'text/plain');
        }
        return $this->contents($uri, File::read($logFile), 'text/plain');
    }
}
