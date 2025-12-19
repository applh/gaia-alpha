<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\User;

class UpdateUserPermissions extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'update_user_permissions',
            'description' => 'Update user permissions or password',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer'],
                    'level' => ['type' => 'integer'],
                    'password' => ['type' => 'string'],
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ],
                'required' => ['user_id']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $userId = $arguments['user_id'] ?? null;
        if (!$userId) {
            throw new \Exception("User ID is required.");
        }
        $success = User::update($userId, $arguments);
        if (!$success) {
            throw new \Exception("Failed to update user $userId.");
        }
        return $this->resultText("User $userId updated.");
    }
}
