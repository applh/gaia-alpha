<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;

class SystemInfo extends BaseTool
{
    public function execute(array $arguments): array
    {
        return $this->resultText('Gaia Alpha v' . Env::get('version') . ' (PHP ' . phpversion() . ')');
    }
}
