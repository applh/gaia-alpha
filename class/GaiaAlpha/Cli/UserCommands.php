<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Database;
use GaiaAlpha\Model\User;

class UserCommands
{
    public static function handleList(): void
    {
        $users = User::findAll();

        if (empty($users)) {
            echo "No users found.\n";
            return;
        }

        echo sprintf("%-5s %-20s %-10s %-20s\n", "ID", "Username", "Level", "Created At");
        echo str_repeat("-", 60) . "\n";

        foreach ($users as $user) {
            echo sprintf(
                "%-5d %-20s %-10d %-20s\n",
                $user['id'],
                $user['username'],
                $user['level'],
                $user['created_at']
            );
        }
    }

    public static function handleCreate(): void
    {
        global $argv;
        if (count($argv) < 4) {
            echo "Usage: user:create <username> <password> [level]\n";
            exit(1);
        }

        $username = $argv[2];
        $password = $argv[3];
        $level = isset($argv[4]) ? (int) $argv[4] : 10;

        // Check if user exists
        if (User::findByUsername($username)) {
            echo "Error: User '$username' already exists.\n";
            exit(1);
        }

        $id = User::create($username, $password, $level);
        echo "User created successfully with ID: $id\n";
    }

    public static function handleUpdatePassword(): void
    {
        global $argv;
        if (count($argv) < 4) {
            echo "Usage: user:update-password <username> <new_password>\n";
            exit(1);
        }

        $username = $argv[2];
        $password = $argv[3];

        $user = User::findByUsername($username);

        if (!$user) {
            echo "Error: User '$username' not found.\n";
            exit(1);
        }

        User::update($user['id'], ['password' => $password]);
        echo "Password updated for user '$username'.\n";
    }

    public static function handleDelete(): void
    {
        global $argv;
        if (count($argv) < 3) {
            echo "Usage: user:delete <username>\n";
            exit(1);
        }

        $username = $argv[2];

        $user = User::findByUsername($username);

        if (!$user) {
            echo "Error: User '$username' not found.\n";
            exit(1);
        }

        User::delete($user['id']);
        echo "User '$username' deleted.\n";
    }
}
