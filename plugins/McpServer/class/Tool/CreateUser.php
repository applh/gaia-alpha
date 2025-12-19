<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\User;

class CreateUser extends BaseTool
{
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
