<?php

namespace JwtAuth\Cli;

use JwtAuth\Service;
use GaiaAlpha\Model\User;

class JwtCommands
{
    /**
     * Generate a JWT for a specific user
     * Usage: php cli.php jwt:generate --user=<username>
     */
    public static function handleGenerate()
    {
        $username = \GaiaAlpha\Cli\Input::getOption('user');
        if (!$username) {
            echo "Error: Missing --user=<username> parameter.\n";
            return;
        }

        $user = User::findByUsername($username);
        if (!$user) {
            echo "Error: User '$username' not found.\n";
            return;
        }

        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'level' => $user['level']
        ];

        $token = Service::generateToken($payload);
        echo "Generated Token for $username:\n\n$token\n";
    }

    /**
     * Verify a JWT token
     * Usage: php cli.php jwt:verify --token=<token>
     */
    public static function handleVerify()
    {
        $token = \GaiaAlpha\Cli\Input::getOption('token');
        if (!$token) {
            echo "Error: Missing --token=<token> parameter.\n";
            return;
        }

        $payload = Service::validateToken($token);
        if ($payload) {
            echo "Token is VALID.\n";
            echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Token is INVALID or EXPIRED.\n";
        }
    }
}
