<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class InstallPlugin extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'install_plugin',
            'description' => 'Install a new plugin (simulated)',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'description' => 'Plugin name']
                ],
                'required' => ['name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $name = $arguments['name'] ?? null;
        if (!$name) {
            throw new \Exception("Plugin name is required.");
        }

        $pluginDir = Env::get('root_dir') . '/plugins/' . $name;
        if (File::isDirectory($pluginDir)) {
            return $this->resultText("Plugin '$name' is already installed.");
        }

        File::makeDirectory($pluginDir);
        File::write($pluginDir . '/plugin.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
            'description' => "Installed via MCP",
            'type' => 'user'
        ], JSON_PRETTY_PRINT));
        File::write($pluginDir . '/index.php', "<?php\n\n// Plugin $name initialized\n");

        return $this->resultText("Plugin '$name' installed successfully (simulated).");
    }
}
