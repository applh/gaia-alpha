<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;
use GaiaAlpha\Service\ScaffoldingService;
use Exception;

class MakeCommands
{
    /**
     * make:plugin <name>
     */
    public static function handlePlugin()
    {
        $name = Input::get(0);
        if (!$name) {
            Output::error("Usage: php cli.php make:plugin <name>");
            return;
        }

        try {
            $service = new ScaffoldingService();
            $result = $service->createPlugin($name);
            Output::success($result['message']);
            foreach ($result['files'] as $file) {
                Output::writeln("  - $file");
            }
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
    }

    /**
     * make:controller <name> --plugin=<plugin>
     */
    public static function handleController()
    {
        $name = Input::get(0);
        $plugin = Input::getOption('plugin');

        if (!$name || !$plugin) {
            Output::error("Usage: php cli.php make:controller <name> --plugin=<plugin_name>");
            return;
        }

        try {
            $service = new ScaffoldingService();
            $result = $service->createController($plugin, $name);
            Output::success($result['message']);
            Output::writeln("  - " . $result['file']);
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
    }

    /**
     * make:mcp-tool <name>
     */
    public static function handleMcpTool()
    {
        $name = Input::get(0);
        if (!$name) {
            Output::error("Usage: php cli.php make:mcp-tool <name>");
            return;
        }

        try {
            $service = new ScaffoldingService();
            $result = $service->createMcpTool($name);
            Output::success($result['message']);
            Output::writeln("  - " . $result['file']);
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
    }

    /**
     * make:mcp-resource <name>
     */
    public static function handleMcpResource()
    {
        $name = Input::get(0);
        if (!$name) {
            Output::error("Usage: php cli.php make:mcp-resource <name>");
            return;
        }

        try {
            $service = new ScaffoldingService();
            $result = $service->createMcpResource($name);
            Output::success($result['message']);
            Output::writeln("  - " . $result['file']);
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
    }

    /**
     * make:mcp-prompt <name>
     */
    public static function handleMcpPrompt()
    {
        $name = Input::get(0);
        if (!$name) {
            Output::error("Usage: php cli.php make:mcp-prompt <name>");
            return;
        }

        try {
            $service = new ScaffoldingService();
            $result = $service->createMcpPrompt($name);
            Output::success($result['message']);
            Output::writeln("  - " . $result['file']);
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
    }
}
