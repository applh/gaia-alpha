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

        // dependencies are now initialized by the commands themselves


        if (count($argv) < 2) {
            self::showHelp();
            exit(1);
        }

        $command = $argv[1];

        try {
            if ($command === 'help') {
                self::showHelp();
                return;
            }

            if ($command === 'sql') {
                $className = TableCommands::class;
                $action = 'handleSql';
            } else {
                $parts = explode(':', $command);
                if (count($parts) !== 2) {
                    echo "Unknown command format: $command\n";
                    self::showHelp();
                    exit(1);
                }

                $group = ucfirst($parts[0]);
                $action = 'handle' . str_replace('-', '', ucwords($parts[1], '-'));

                $className = "GaiaAlpha\\Cli\\{$group}Commands";
            }

            if (!class_exists($className)) {
                echo "Unknown command group: {$parts[0]}\n";
                self::showHelp();
                exit(1);
            }

            if (!method_exists($className, $action)) {
                echo "Unknown action: {$parts[1]} for group {$parts[0]}\n";
                self::showHelp();
                exit(1);
            }

            Hook::run('cli_command_before', $command, $className, $action);

            call_user_func([$className, $action]);

            Hook::run('cli_command_after', $command);

        } catch (Exception $e) {
            Hook::run('cli_exception', $e);
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private static function showHelp(): void
    {
        $templatePath = Env::get('root_dir') . '/templates/cli_help.txt';
        if (file_exists($templatePath)) {
            echo file_get_contents($templatePath) . "\n";
        } else {
            echo "Help template not found.\n";
        }
    }
}
