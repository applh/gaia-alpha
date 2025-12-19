<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\User;

class CreateUser extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'create_user',
            'description' => 'Create a new user',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'username' => ['type' => 'string'],
                    'password' => ['type' => 'string'],
                    'level' => ['type' => 'integer', 'description' => 'Access level (10=member, 100=admin)'],
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ],
                'required' => ['username', 'password']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $username = $arguments['username'] ?? null;
        $password = $arguments['password'] ?? null;
        if (!$username || !$password) {
            throw new \Exception("Username and password are required.");
        }
        $level = $arguments['level'] ?? 10;
        $id = User::create($username, $password, $level);
        return $this->resultText("User '$username' created with ID $id.");
    }
}
