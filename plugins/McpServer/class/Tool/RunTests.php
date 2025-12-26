<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;

class RunTests extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'run_tests',
            'description' => 'Run the project test suite or specific tests',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Relative path to a test file or directory (e.g. "tests/Api" or "plugins/JwtAuth/tests"). If omitted, runs all tests.'
                    ]
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $path = $arguments['path'] ?? '';
        $rootDir = Env::get('root_dir');

        // Sanitize path (basic prevention of escaping root)
        $path = str_replace('..', '', $path);
        $path = ltrim($path, '/');

        $cmd = 'php ' . escapeshellarg($rootDir . '/tests/run.php');

        if ($path) {
            $fullPath = $rootDir . '/' . $path;
            if (!file_exists($fullPath)) {
                return $this->resultJson([
                    'status' => 'error',
                    'message' => "Path not found: $path"
                ]);
            }
            $cmd .= ' ' . escapeshellarg($fullPath);
        }

        // Execute command
        $output = [];
        $returnVar = 0;
        exec($cmd . ' 2>&1', $output, $returnVar);

        $outputText = implode("\n", $output);

        // Parse summary if possible (simple regex to check pass/fail)
        $status = ($returnVar === 0) ? 'success' : 'failure';

        return $this->resultJson([
            'status' => $status,
            'command' => $cmd,
            'output' => $outputText
        ]);
    }
}
