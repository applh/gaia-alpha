<?php

namespace GaiaAlpha\Service;

use Exception;
use GaiaAlpha\Env;

class ScaffoldingService
{
    /**
     * Create a new plugin directory structure and base files.
     */
    public function createPlugin(string $name): array
    {
        $name = ucfirst($name);
        $pluginDir = Env::get('root_dir') . '/plugins/' . $name;

        if (is_dir($pluginDir)) {
            throw new Exception("Plugin '$name' already exists.");
        }

        // Create directories
        $dirs = [
            $pluginDir,
            $pluginDir . '/class',
            $pluginDir . '/class/Controller',
            $pluginDir . '/class/Model',
            $pluginDir . '/class/Tool',
            $pluginDir . '/resources',
            $pluginDir . '/resources/js',
        ];

        foreach ($dirs as $dir) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }

        // Create index.php
        $indexContent = $this->getPluginIndexTemplate($name);
        file_put_contents($pluginDir . '/index.php', $indexContent);

        // Create plugin.json
        $jsonContent = json_encode([
            'name' => $name,
            'version' => '1.0.0',
            'description' => "Description for $name plugin",
            'author' => 'Gaia Alpha',
        ], JSON_PRETTY_PRINT);
        file_put_contents($pluginDir . '/plugin.json', $jsonContent);

        // Create docs file
        $docsDir = Env::get('root_dir') . '/docs/plugins';
        if (!is_dir($docsDir)) {
            mkdir($docsDir, 0755, true);
        }
        $docContent = "# $name Plugin\n\nObjective: ...\n\n## Configuration\n\n## Hooks\n\n## CLI/MCP\n";
        file_put_contents($docsDir . '/' . $name . '.md', $docContent);

        return [
            'success' => true,
            'message' => "Plugin '$name' created successfully.",
            'files' => [
                "plugins/$name/index.php",
                "plugins/$name/plugin.json",
                "docs/plugins/$name.md"
            ]
        ];
    }

    /**
     * Create a new controller in a plugin.
     */
    public function createController(string $plugin, string $name): array
    {
        $plugin = ucfirst($plugin);
        $name = ucfirst($name);
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $pluginDir = Env::get('root_dir') . '/plugins/' . $plugin;
        if (!is_dir($pluginDir)) {
            throw new Exception("Plugin '$plugin' does not exist.");
        }

        $controllerDir = $pluginDir . '/class/Controller';
        if (!is_dir($controllerDir)) {
            mkdir($controllerDir, 0755, true);
        }

        $file = $controllerDir . '/' . $name . '.php';
        if (file_exists($file)) {
            throw new Exception("Controller '$name' already exists in plugin '$plugin'.");
        }

        $content = $this->getControllerTemplate($plugin, $name);
        file_put_contents($file, $content);

        return [
            'success' => true,
            'message' => "Controller '$name' created in plugin '$plugin'.",
            'file' => "plugins/$plugin/class/Controller/$name.php"
        ];
    }

    /**
     * Create a new MCP Tool.
     */
    public function createMcpTool(string $name): array
    {
        $name = ucfirst($name);
        $mcpPluginDir = Env::get('root_dir') . '/plugins/McpServer';
        $toolDir = $mcpPluginDir . '/class/Tool';

        if (!is_dir($toolDir)) {
            mkdir($toolDir, 0755, true);
        }

        $file = $toolDir . '/' . $name . '.php';
        if (file_exists($file)) {
            throw new Exception("MCP Tool '$name' already exists.");
        }

        $content = $this->getMcpToolTemplate($name);
        file_put_contents($file, $content);

        return [
            'success' => true,
            'message' => "MCP Tool '$name' created.",
            'file' => "plugins/McpServer/class/Tool/$name.php"
        ];
    }

    /**
     * Create a new MCP Resource.
     */
    public function createMcpResource(string $name): array
    {
        $name = ucfirst($name);
        $mcpPluginDir = Env::get('root_dir') . '/plugins/McpServer';
        $resourceDir = $mcpPluginDir . '/class/Resource';

        if (!is_dir($resourceDir)) {
            mkdir($resourceDir, 0755, true);
        }

        $file = $resourceDir . '/' . $name . '.php';
        if (file_exists($file)) {
            throw new Exception("MCP Resource '$name' already exists.");
        }

        $content = $this->getMcpResourceTemplate($name);
        file_put_contents($file, $content);

        return [
            'success' => true,
            'message' => "MCP Resource '$name' created.",
            'file' => "plugins/McpServer/class/Resource/$name.php"
        ];
    }

    /**
     * Create a new MCP Prompt.
     */
    public function createMcpPrompt(string $name): array
    {
        $name = ucfirst($name);
        $mcpPluginDir = Env::get('root_dir') . '/plugins/McpServer';
        $promptDir = $mcpPluginDir . '/class/Prompt';

        if (!is_dir($promptDir)) {
            mkdir($promptDir, 0755, true);
        }

        $file = $promptDir . '/' . $name . '.php';
        if (file_exists($file)) {
            throw new Exception("MCP Prompt '$name' already exists.");
        }

        $content = $this->getMcpPromptTemplate($name);
        file_put_contents($file, $content);

        return [
            'success' => true,
            'message' => "MCP Prompt '$name' created.",
            'file' => "plugins/McpServer/class/Prompt/$name.php"
        ];
    }

    private function getPluginIndexTemplate(string $name): string
    {
        $lowerName = strtolower($name);
        return <<<PHP
<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
// use {$name}\Controller\{$name}Controller;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function (\$controllers) {
    \$controllers = Env::get('controllers');

    /*
    if (class_exists({$name}Controller::class)) {
        \$controller = new {$name}Controller();
        if (method_exists(\$controller, 'init')) {
            \$controller->init();
        }
        if (method_exists(\$controller, 'registerRoutes')) {
            \$controller->registerRoutes();
        }
        \$controllers['{$lowerName}'] = \$controller;
        Env::set('controllers', \$controllers);
    }
    */
});

// 2. Register UI Component
// \GaiaAlpha\UiManager::registerComponent('{$lowerName}', 'plugins/{$name}/resources/js/Main.js', true);

PHP;
    }

    private function getControllerTemplate(string $plugin, string $name): string
    {
        $lowerPlugin = strtolower($plugin);
        return <<<PHP
<?php

namespace {$plugin}\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use GaiaAlpha\Request;
use GaiaAlpha\Response;

class {$name} extends BaseController
{
    /**
     * Required: Register your routes here.
     */
    public function registerRoutes()
    {
        \$prefix = '/@/{$lowerPlugin}';
        Router::add('GET', \$prefix . '/items', [\$this, 'index']);
    }

    public function index()
    {
        \$this->requireAuth();
        Response::json(['success' => true, 'plugin' => '{$plugin}', 'controller' => '{$name}']);
    }
}
PHP;
    }

    private function getMcpToolTemplate(string $name): string
    {
        $toolName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        return <<<PHP
<?php

namespace McpServer\Tool;

class {$name} extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => '{$toolName}',
            'description' => 'Description for {$toolName} tool',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'arg1' => ['type' => 'string', 'description' => 'Argument description']
                ],
                'required' => []
            ]
        ];
    }

    public function execute(array \$arguments): array
    {
        // \$arg1 = \$arguments['arg1'] ?? null;
        return \$this->resultText("Tool {$toolName} executed successfully.");
    }
}
PHP;
    }

    private function getMcpResourceTemplate(string $name): string
    {
        $resourceUri = "cms://" . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . "/list";
        return <<<PHP
<?php

namespace McpServer\Resource;

class {$name} extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => '{$resourceUri}',
            'name' => '{$name} Resource',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string \$uri): ?array
    {
        return \$uri === '{$resourceUri}' ? [] : null;
    }

    public function read(string \$uri, array \$matches): array
    {
        return \$this->contents(\$uri, json_encode(['items' => []], JSON_PRETTY_PRINT));
    }
}
PHP;
    }

    private function getMcpPromptTemplate(string $name): string
    {
        $promptName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        return <<<PHP
<?php

namespace McpServer\Prompt;

class {$name} extends BasePrompt
{
    public function getDefinition(): array
    {
        return [
            'name' => '{$promptName}',
            'description' => 'Description for {$promptName} prompt',
            'arguments' => [
                [
                    'name' => 'arg1',
                    'description' => 'Argument description',
                    'required' => false
                ]
            ]
        ];
    }

    public function getPrompt(array \$arguments): array
    {
        return [
            'description' => 'Prompt description',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => "Hello, this is a prompt for {$promptName}."
                    ]
                ]
            ]
        ];
    }
}
PHP;
    }
}
