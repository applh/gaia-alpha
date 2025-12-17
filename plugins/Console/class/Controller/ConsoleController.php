<?php

namespace Console\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Framework;
use GaiaAlpha\System;
use GaiaAlpha\Env;
use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Debug;
use GaiaAlpha\Cli;

class ConsoleController extends BaseController
{
    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('POST', '/@/console/run', [$this, 'run']);
    }

    public function run()
    {
        \GaiaAlpha\Session::requireLevel(100);

        $input = Request::input();
        $rawCommand = $input['command'] ?? '';

        if (empty($rawCommand)) {
            Response::json(['error' => 'No command provided'], 400);
            return;
        }

        // Security: Ensure we are only running the intended CLI script
        // We only allow arguments, not the executable logic change
        // Users should input: "db:list users", "media:info file.mp4"

        $parts = $this->parseCommand($rawCommand);
        if (empty($parts)) {
            Response::json(['error' => 'Invalid command'], 400);
            return;
        }

        $cliScript = Env::get('root_dir') . '/cli.php';

        // Build safe command using System::escapeArg for each part
        $safeArgs = [];
        foreach ($parts as $part) {
            $safeArgs[] = System::escapeArg($part);
        }

        $fullCommand = 'php ' . System::escapeArg($cliScript) . ' ' . implode(' ', $safeArgs);

        // Capture output
        $output = [];
        $returnVar = 0;

        // Use System::exec for consistent behavior and hooks
        System::exec($fullCommand . ' 2>&1', $output, $returnVar);

        Response::json([
            'command' => $rawCommand,
            'output' => implode("\n", $output),
            'status' => $returnVar
        ]);
    }

    /**
     * Simple parser to handle quoted strings in command
     */
    private function parseCommand($str)
    {
        $parts = [];
        $current = '';
        $inQuote = false;

        // Simple tokenization
        // Supports basic quotes for arguments with spaces
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];

            if ($char === '"' || $char === "'") {
                $inQuote = !$inQuote;
                // Don't include quotes in the argument value itself?
                // Actually, for CLI args, usually we strip the outer quotes.
                continue;
            }

            if ($char === ' ' && !$inQuote) {
                if ($current !== '') {
                    $parts[] = $current;
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }
}
