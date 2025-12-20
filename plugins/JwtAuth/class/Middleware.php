<?php

namespace JwtAuth;

use GaiaAlpha\Request;
use GaiaAlpha\Session;

class Middleware
{
    /**
     * Handle the request and check for JWT
     */
    public static function handle()
    {
        $authHeader = Request::header('Authorization');

        // Also check if token is passed via query string for convenience (optional)
        $token = null;
        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        } else {
            $token = Request::query('token');
        }

        if (!$token) {
            return;
        }

        $payload = Service::validateToken($token);

        if ($payload && isset($payload['user_id'])) {
            // Mock session for the current request
            // We start the session if it hasn't been started yet
            Session::start();

            $_SESSION['user_id'] = $payload['user_id'];
            $_SESSION['username'] = $payload['username'] ?? 'jwt_user';
            $_SESSION['level'] = (int) ($payload['level'] ?? 10);

            // Flag to indicate this session is via JWT authentication
            $_SESSION['jwt_mode'] = true;

            // We might want to disable session cookie sending if possible, 
            // but for simplicity in Gaia Alpha, we just populate $_SESSION.
            // Note: If this persists, the next request might "stay" logged in via cookie.
            // This is actually how some "remember me" or SSO flows work.
        }
    }
}
