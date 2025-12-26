<?php

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Request;
use GaiaAlpha\Env;

class ContextTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('GAIA_TEST_HTTP')) {
            define('GAIA_TEST_HTTP', true);
        }
        // Reset server variables for each test
        $_SERVER['REQUEST_URI'] = '/';
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        Env::set('admin_prefixes', null);
        Env::set('app_prefixes', null);
        Env::set('api_prefixes', null);
    }

    public function testPublicContext()
    {
        $_SERVER['REQUEST_URI'] = '/some-page';
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('public', Request::context());
    }

    public function testApiContext()
    {
        $_SERVER['REQUEST_URI'] = '/@/api/todos';
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('api', Request::context());
    }

    public function testAdminContext()
    {
        $_SERVER['REQUEST_URI'] = '/@/admin/dashboard';
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('admin', Request::context());
    }

    public function testAppContext()
    {
        $_SERVER['REQUEST_URI'] = '/@/app/my-plugin';
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('app', Request::context());
    }

    public function testConfigurablePrefixes()
    {
        Env::set('api_prefixes', ['/custom-api']);
        $_SERVER['REQUEST_URI'] = '/custom-api/resource';
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('api', Request::context());

        Env::set('admin_prefixes', ['/my-admin']);
        $_SERVER['REQUEST_URI'] = '/my-admin/stats';
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('admin', Request::context());

        Env::set('app_prefixes', ['/my-app']);
        $_SERVER['REQUEST_URI'] = '/my-app/editor';
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('app', Request::context());
    }

    public function testPluginFilteringLogic()
    {
        $_SERVER['REQUEST_URI'] = '/@/api/something';
        $currentContext = Request::context();
        \GaiaAlpha\Tests\Framework\Assert::assertEquals('api', $currentContext);

        $mockPlugins = [
            ['name' => 'ApiOnly', 'context' => 'api'],
            ['name' => 'AdminOnly', 'context' => 'admin'],
            ['name' => 'AllContexts', 'context' => 'all']
        ];

        $loaded = [];
        foreach ($mockPlugins as $p) {
            if ($p['context'] === 'all' || $p['context'] === $currentContext) {
                $loaded[] = $p['name'];
            }
        }

        \GaiaAlpha\Tests\Framework\Assert::assertCount(2, $loaded);
        \GaiaAlpha\Tests\Framework\Assert::assertTrue(in_array('ApiOnly', $loaded));
        \GaiaAlpha\Tests\Framework\Assert::assertTrue(in_array('AllContexts', $loaded));
        \GaiaAlpha\Tests\Framework\Assert::assertFalse(in_array('AdminOnly', $loaded));
    }
}
