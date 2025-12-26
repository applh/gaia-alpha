<?php

namespace JwtAuth;

use GaiaAlpha\Middleware;
use GaiaAlpha\Request;
use GaiaAlpha\Session;

class JwtAuthMiddleware implements Middleware
{
    /**
     * Handle the request and check for JWT
     * 
     * @param \Closure $next
     * @return mixed
     */
    public function handle(\Closure $next)
    {
        $authHeader = Request::header('Authorization');

        // Also check if token is passed via query string for convenience (optional)
        $token = null;
        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        } else {
            $token = Request::query('token');
        }

        if ($token) {
            $payload = Service::validateToken($token);

            if ($payload && isset($payload['user_id'])) {
                // Mock session for the current request
                Session::start();

                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['username'] = $payload['username'] ?? 'jwt_user';
                $_SESSION['level'] = (int) ($payload['level'] ?? 10);

                // Flag to indicate this session is via JWT authentication
                $_SESSION['jwt_mode'] = true;
            }
        }

        return $next();
    }
}
