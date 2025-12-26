<?php

namespace GaiaAlpha\Tests\Api;

use GaiaAlpha\Tests\Framework\ApiTestCase;
use GaiaAlpha\Tests\Framework\Assert;
use GaiaAlpha\Request;
use GaiaAlpha\Session;

// Define GAIA_TEST_HTTP if not already defined (handled in bootstrap usually)
if (!defined('GAIA_TEST_HTTP'))
    define('GAIA_TEST_HTTP', true);

class SampleJwtTest extends ApiTestCase
{
    public function setUp()
    {
        parent::setUp();
        // Reset Request state
        Request::mock([], [], [], $_SERVER);
    }

    public function testUnauthenticatedAccess()
    {
        // Without actingAs, we should have no user
        Assert::assertFalse(Session::isLoggedIn(), "User should not be logged in initially");
    }

    public function testActingAs()
    {
        // 1. Authenticate
        $this->actingAs([
            'id' => 999,
            'username' => 'api_tester',
            'level' => 100
        ]);

        // 2. Simulate Middleware Execution
        // In a real full-stack test, middleware runs automatically.
        // In this unit-style environment, we must invoke the middleware manually 
        // OR rely on the App dispatch logic if we were running a full request.
        // Since our TestRunner is unit-based, we call the Middleware mechanism here to prove it works.

        $middleware = new \JwtAuth\JwtAuthMiddleware();
        $middleware->handle(function () {
            // Inside the controller/next closure:

            // Verify Session is populated from Token
            Assert::assertTrue(Session::isLoggedIn(), "Session should be logged in after middleware");
            Assert::assertEquals(999, Session::id());
            Assert::assertEquals('api_tester', Session::get('username'));
        });
    }
}
