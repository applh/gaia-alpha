<?php

namespace GaiaAlpha;

use Exception;
use GaiaAlpha\Env;
use GaiaAlpha\Controller\DbController;
use GaiaAlpha\Cli\TableCommands;
use GaiaAlpha\Cli\FileCommands;
use GaiaAlpha\Cli\MediaCommands;
use GaiaAlpha\Cli\VendorCommands;

class Cli
{
    public static function run(): void
    {
        global $argv;

        // Initialize connections
        $db = DbController::connect();
        $pdo = $db->getPdo();
        $media = new Media(Env::get('path_data'));

        // Inject dependencies
        TableCommands::setPdo($pdo);
        MediaCommands::setMedia($media);

        if (count($argv) < 2) {
            self::showHelp();
            exit(1);
        }

        $command = $argv[1];

        try {
            switch ($command) {
                case 'table:list':
                    TableCommands::handleList($argv);
                    break;
                case 'table:insert':
                    TableCommands::handleInsert($argv);
                    break;
                case 'table:update':
                    TableCommands::handleUpdate($argv);
                    break;
                case 'table:delete':
                    TableCommands::handleDelete($argv);
                    break;
                case 'sql':
                    TableCommands::handleSql($argv);
                    break;
                case 'media:stats':
                    MediaCommands::handleStats();
                    break;
                case 'media:clear-cache':
                    MediaCommands::handleClearCache();
                    break;
                case 'file:write':
                    FileCommands::handleWrite($argv);
                    break;
                case 'file:read':
                    FileCommands::handleRead($argv);
                    break;
                case 'file:list':
                    FileCommands::handleList($argv);
                    break;
                case 'file:delete':
                    FileCommands::handleDelete($argv);
                    break;
                case 'file:move':
                    FileCommands::handleMove($argv);
                    break;
                case 'vendor:update':
                    VendorCommands::handleUpdate();
                    break;
                case 'help':
                    self::showHelp();
                    break;
                default:
                    echo "Unknown command: $command\n";
                    self::showHelp();
                    exit(1);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private static function showHelp(): void
    {
        echo "Usage: php cli.php <command> [arguments]\n\n";
        echo "Commands:\n";
        echo "  table:list <table>                  List all rows in a table\n";
        echo "  table:insert <table> <json_data>    Insert a row (e.g. '{\"col\":\"val\"}')\n";
        echo "  table:update <table> <id> <json>    Update a row by ID\n";
        echo "  table:delete <table> <id>           Delete a row by ID\n";
        echo "  sql <query>                         Execute a raw SQL query\n";
        echo "  media:stats                         Show storage stats for uploads and cache\n";
        echo "  media:clear-cache                   Clear all cached images\n";
        echo "  file:write <path> <content>         Write content to a file in my-data\n";
        echo "  file:read <path>                    Read content from a file in my-data\n";
        echo "  file:list [path]                    List files in my-data (or subdirectory)\n";
        echo "  file:delete <path>                  Delete a file in my-data\n";
        echo "  file:move <source> <destination>    Move/rename a file in my-data\n";
        echo "  vendor:update                       Update vendor libraries (Leaflet, Vue)\n";
        echo "  help                                Show this help message\n";
    }
}
