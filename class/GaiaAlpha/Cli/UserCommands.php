<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Database;
use GaiaAlpha\Model\User;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class UserCommands
{
    public static function handleList(): void
    {
        $users = User::findAll();

        if (empty($users)) {
            Output::info("No users found.");
            return;
        }

        $headers = ["ID", "Username", "Level", "Created At"];
        $rows = array_map(function ($user) {
            return [
                $user['id'],
                $user['username'],
                $user['level'],
                $user['created_at']
            ];
        }, $users);

        Output::table($headers, $rows);
    }

    public static function handleCreate(): void
    {
        if (Input::count() < 2) {
            Output::writeln("Usage: user:create <username> <password> [level]");
            exit(1);
        }

        $username = Input::get(0);
        $password = Input::get(1);
        $level = (int) Input::get(2, 10);

        // Check if user exists
        if (User::findByUsername($username)) {
            Output::error("User '$username' already exists.");
            exit(1);
        }

        $id = User::create($username, $password, $level);
        Output::success("User created successfully with ID: $id");
    }

    public static function handleUpdatePassword(): void
    {
        if (Input::count() < 2) {
            Output::writeln("Usage: user:update-password <username> <new_password>");
            exit(1);
        }

        $username = Input::get(0);
        $password = Input::get(1);

        $user = User::findByUsername($username);

        if (!$user) {
            Output::error("User '$username' not found.");
            exit(1);
        }

        User::update($user['id'], ['password' => $password]);
        Output::success("Password updated for user '$username'.");
    }

    public static function handleDelete(): void
    {
        if (Input::count() < 1) {
            Output::writeln("Usage: user:delete <username>");
            exit(1);
        }

        $username = Input::get(0);

        $user = User::findByUsername($username);

        if (!$user) {
            Output::error("User '$username' not found.");
            exit(1);
        }

        User::delete($user['id']);
        Output::success("User '$username' deleted.");
    }
}
