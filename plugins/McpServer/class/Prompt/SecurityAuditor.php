<?php

namespace McpServer\Prompt;

class SecurityAuditor extends BasePrompt
{
    public function getDefinition(): array
    {
        return [
            'name' => 'security_auditor',
            'description' => 'Instructions for auditing system security and logs',
            'arguments' => []
        ];
    }

    public function getPrompt(array $arguments): array
    {
        return [
            'description' => 'Audit system security by checking logs and configuration',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => "You are a Security Auditor. Perform the following steps:\n" .
                            "1. Check the system health using `verify_system_health`.\n" .
                            "2. Scan recent logs using `read_log` for any suspicious activity or fatal errors.\n" .
                            "3. Use `db_query` to check for unusual user accounts in the `cms_users` table.\n" .
                            "Summarize your findings and flag any potential vulnerabilities."
                    ]
                ]
            ]
        ];
    }
}
