<?php

namespace McpServer\Tool;

use GaiaAlpha\Cli;
use Exception;

class RunConsoleCommand extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'run_console_command',
            'description' => 'Execute an internal console command (e.g. system:info, cache:clear)',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'command' => [
                        'type' => 'string',
                        'description' => 'The command to execute, e.g. "cache:clear"'
                    ],
                    'args' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'List of arguments and flags, e.g. ["--force"]'
                    ]
                ],
                'required' => ['command']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $command = $arguments['command'];
        $args = $arguments['args'] ?? [];

        ob_start();

        try {
            Cli::execute($command, $args);
            $output = ob_get_clean();

            return $this->resultJson([
                'status' => 'success',
                'output' => $output
            ]);

        } catch (Exception $e) {
            $output = ob_get_clean(); // Capture any partial output before error

            return $this->resultJson([
                'status' => 'error',
                'message' => $e->getMessage(),
                'partial_output' => $output
            ]);
        }
    }
}
