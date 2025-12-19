<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\User;

class ListUsers extends BaseTool
{
    public function execute(array $arguments): array
    {
        $users = User::findAll();
        return $this->resultJson($users);
    }
}
