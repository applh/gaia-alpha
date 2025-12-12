<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Framework;
use GaiaAlpha\System;
use GaiaAlpha\Env;
use GaiaAlpha\Hook;

class ConsoleController extends BaseController
{
    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('POST', '/api/console/run', [$this, 'run']);
    }

    public function run()
    {
        Framework::checkAuth(100);

        $input = Framework::decodeBody();
        $rawCommand = $input['command'] ?? '';

        if (empty($rawCommand)) {
            Framework::json(['error' => 'No command provided'], 400);
            return;
        }

        // Security: Ensure we are only running the intended CLI script
        // We only allow arguments, not the executable logic change
        // Users should input: "db:list users", "media:info file.mp4"

        $parts = $this->parseCommand($rawCommand);
        if (empty($parts)) {
            Framework::json(['error' => 'Invalid command'], 400);
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

        Framework::json([
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
