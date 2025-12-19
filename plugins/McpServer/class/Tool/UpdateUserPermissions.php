<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\User;

class UpdateUserPermissions extends BaseTool
{
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
