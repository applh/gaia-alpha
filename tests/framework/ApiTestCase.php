<?php

namespace GaiaAlpha\Tests\Framework;

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Request.php';

// Ensure JwtAuth Service is available (assume plugin is loaded or directly include)
// In a real env, App::registerAutoloaders() handles this.
// For safety in tests, we can check.

class ApiTestCase extends TestCase
{
    /**
     * Authenticate as a specific user for the next request.
     * Generates a JWT and sets the HTTP_AUTHORIZATION header.
     * 
     * @param array $user User array (id, username, level)
     * @return void
     */
    public function actingAs(array $user)
    {
        // 1. Check if JwtAuth plugin class is available
        if (!class_exists('JwtAuth\\Service')) {
            throw new \Exception("JwtAuth plugin is not loaded. Cannot use actingAs().");
        }

        // 2. Generate Token
        $token = \JwtAuth\Service::generateToken([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'level' => $user['level'] ?? 10
        ]);

        // 3. Inject into Server globals (which Request::header() reads)
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    /**
     * Reset auth headers after test
     */
    public function tearDown()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            unset($_SERVER['HTTP_AUTHORIZATION']);
        }
        parent::tearDown();
    }
}
