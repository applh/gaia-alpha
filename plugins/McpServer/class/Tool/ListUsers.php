<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\User;

class ListUsers extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'list_users',
            'description' => 'List all users for a specific site',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $users = User::findAll();
        return $this->resultJson($users);
    }
}
