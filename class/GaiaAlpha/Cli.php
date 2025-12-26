<?php

namespace GaiaAlpha;

use Exception;
use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use GaiaAlpha\Controller\DbController;
use GaiaAlpha\Cli\TableCommands;
use GaiaAlpha\Cli\FileCommands;
use GaiaAlpha\Cli\MediaCommands;
use GaiaAlpha\Cli\VendorCommands;
use GaiaAlpha\Cli\UserCommands;

class Cli
{
    public static function run(): void
    {
        global $argv;

        Hook::run('cli_init');

        $args = $argv ?? [];
        $command = null;
        $commandIndex = -1;

        // Skip script name [0] and find the first argument that is NOT a flag
        for ($i = 1; $i < count($args); $i++) {
            if (!str_starts_with($args[$i], '--')) {
                $command = $args[$i];
                $commandIndex = $i;
                break;
            }
        }

        if (!$command) {
            self::showHelp();
            exit(1);
        }

        // Initialize Input with arguments following the command
        $inputArgs = array_slice($args, $commandIndex + 1);

        try {
            self::execute($command, $inputArgs);
        } catch (Exception $e) {
            Hook::run('cli_exception', $e);
            \GaiaAlpha\Cli\Output::error($e->getMessage());
            exit(1);
        }
    }

    public static function execute(string $command, array $args = []): void
    {
        \GaiaAlpha\Cli\Input::initFromArgv($args);

        if ($command === 'help') {
            self::showHelp();
            return;
        }

        if ($command === 'sql') {
            $className = TableCommands::class;
            $action = 'handleQuery';
        } else {
            $parts = explode(':', $command);
            if (count($parts) !== 2) {
                // Throwing exception instead of exit to allow catching in basic usage
                throw new Exception("Unknown command format: $command");
            }

            $group = ucfirst($parts[0]);
            $action = 'handle' . str_replace('-', '', ucwords($parts[1], '-'));

            $className = "GaiaAlpha\\Cli\\{$group}Commands";
        }

        // Hook for plugins to resolve commands
        if (!class_exists($className)) {
            $groupName = isset($parts) ? $parts[0] : $command;
            $resolved = \GaiaAlpha\Hook::filter('cli_resolve_command', null, $groupName, $parts ?? null);
            if ($resolved && class_exists($resolved)) {
                $className = $resolved;
            }
        }

        if (!class_exists($className)) {
            throw new Exception("Unknown command group: " . ($parts[0] ?? $command));
        }

        if (!method_exists($className, $action)) {
            throw new Exception("Unknown action: " . ($parts[1] ?? $action) . " for  " . ($parts[0] ?? $command));
        }

        Hook::run('cli_command_before', $command, $className, $action);

        call_user_func([$className, $action]);

        Hook::run('cli_command_after', $command);
    }

    private static function showHelp(): void
    {
        $templatePath = Env::get('root_dir') . '/templates/cli_help.txt';
        if (file_exists($templatePath)) {
            \GaiaAlpha\Cli\Output::writeln(file_get_contents($templatePath));
        } else {
            \GaiaAlpha\Cli\Output::error("Help template not found.");
        }
    }
}
