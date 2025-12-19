<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;

class SystemInfo extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'system_info',
            'description' => 'Get system version and status',
            'inputSchema' => ['type' => 'object', 'properties' => (object) []]
        ];
    }

    public function execute(array $arguments): array
    {
        return $this->resultText('Gaia Alpha v' . Env::get('version') . ' (PHP ' . phpversion() . ')');
    }
}
