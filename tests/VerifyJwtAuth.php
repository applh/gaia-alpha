<?php

namespace GaiaAlpha\Tests;

require_once __DIR__ . '/../class/GaiaAlpha/App.php';
require_once __DIR__ . '/../class/GaiaAlpha/Env.php';
require_once __DIR__ . '/../class/GaiaAlpha/Hook.php';

// Setup Mock Env
\GaiaAlpha\Env::set('root_dir', realpath(__DIR__ . '/../'));
\GaiaAlpha\Env::set('path_data', realpath(__DIR__ . '/../my-data'));

// Define GAIA_TEST_HTTP to prevent CLI context forcing in Request::context()
if (!defined('GAIA_TEST_HTTP')) {
    define('GAIA_TEST_HTTP', true);
}

// Register Autoloaders
\GaiaAlpha\App::registerAutoloaders();

use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Router;
use GaiaAlpha\Controller\AuthController;
use GaiaAlpha\Model\User;

class VerifyJwtAuth
{
    public function run()
    {
        echo "Testing JWT Authentication Integration...\n";

        // 1. Mock Login
        echo "Step 1: Mocking Login...\n";
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/@/api/login';

        // We need a user in the DB. Let's try to find one or assume 'admin' exists.
        // For this test, we'll mock User::findByUsername
        // (In a real test we'd use a test DB, but here we want to verify the logic flow)

        $mockUser = [
            'id' => 1,
            'username' => 'testuser',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'level' => 100
        ];

        // We can't easily mock User::findByUsername without changing its code or using a mock DB.
        // Let's assume the user 'admin' exists if this is a real environment.
        // Or better, let's just test the Token generation and Middleware directly first.

        echo "Step 2: Testing Token Generation...\n";
        if (!class_exists('JwtAuth\\Service')) {
            die("Error: JwtAuth\\Service not found. Plugin not loaded?\n");
        }

        $token = \JwtAuth\Service::generateToken([
            'user_id' => 1,
            'username' => 'testuser',
            'level' => 100
        ]);
        echo "Generated Token: " . substr($token, 0, 20) . "...\n";

        // 3. Test Middleware
        echo "Step 3: Testing Middleware Authentication...\n";
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/@/api/user';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        // Reset Request state
        Request::mock([], [], [], $_SERVER);

        $middleware = new \JwtAuth\JwtAuthMiddleware();
        $middleware->handle(function () {
            // Final destination
            if (\GaiaAlpha\Session::isLoggedIn()) {
                echo "SUCCESS: User is logged in via JWT!\n";
                echo "Username: " . \GaiaAlpha\Session::get('username') . "\n";
                echo "Level: " . \GaiaAlpha\Session::level() . "\n";
            } else {
                echo "FAILURE: User is NOT logged in via JWT.\n";
            }
        });

        echo "\nVerification Complete.\n";
    }
}

$test = new VerifyJwtAuth();
$test->run();
