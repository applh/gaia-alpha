<?php

namespace McpServer\Prompt;

class SummarizeHealth extends BasePrompt
{
    public function getDefinition(): array
    {
        return [
            'name' => 'summarize_health',
            'description' => 'Check system health and summarize findings',
            'arguments' => []
        ];
    }

    public function getPrompt(array $arguments): array
    {
        return [
            'description' => 'Check system health and summarize findings',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => "Please run the 'verify_system_health' and 'read_log' tools to check the current system state, and then provide a concise summary of the health status and any active errors."
                    ]
                ]
            ]
        ];
    }
}
