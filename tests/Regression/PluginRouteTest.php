<?php

namespace GaiaAlpha\Tests\Regression;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;
use GaiaAlpha\Framework;
use GaiaAlpha\App;
use GaiaAlpha\Router;
use GaiaAlpha\Env;

class PluginRouteTest extends TestCase
{
    private $testDataPath;

    public function setUp()
    {
        parent::setUp();

        // Define a safe test data path
        $this->testDataPath = Env::get('root_dir') . '/tests/my-data-route-test';

        // Cleanup if exists
        $this->cleanup();
        mkdir($this->testDataPath, 0755, true);

        // Create active_plugins.json with McpServer
        file_put_contents($this->testDataPath . '/active_plugins.json', json_encode(['McpServer']));

        // Override path_data
        Env::set('path_data', $this->testDataPath);
    }

    public function tearDown()
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function cleanup()
    {
        if (is_dir($this->testDataPath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->testDataPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($this->testDataPath);
        }
    }

    public function testPluginRoutesAreRegistered()
    {
        // 1. Initialize App Environment
        App::init();
        Env::set('controllers', []);

        // 2. Load Plugins (this registers controllers to Env)
        Framework::loadPlugins();

        // 2.5 Load Controllers
        Framework::loadControllers();

        // 3. Register Routes (this executes registerRoutes() on all controllers)
        Framework::registerRoutes();

        // 4. Introspect Routes
        $routes = Router::getRoutes();

        // 5. Verify Static Routes
        $staticGet = $routes['static']['GET'] ?? [];
        $staticPost = $routes['static']['POST'] ?? [];

        // Flatten keys for easy searching
        $staticPaths = array_merge(array_keys($staticGet), array_keys($staticPost));

        // CHECK: McpServer Routes
        if (in_array('McpServer', $this->getActivePlugins())) {
            Assert::assertTrue(
                $this->routeExists($staticPaths, '/@/mcp/stream'),
                'McpServer Stream route /@/mcp/stream not registered'
            );
            Assert::assertTrue(
                $this->routeExists($staticPaths, '/@/mcp/stats'),
                'McpServer Stats route /@/mcp/stats not registered'
            );
        }

        // CHECK: Ecommerce Routes (if active)
        if (in_array('Ecommerce', $this->getActivePlugins())) {
            // Heuristic check for common ecommerce route
            Assert::assertTrue(
                $this->routeExists($staticPaths, '/@/ecommerce/dashboard') ||
                $this->routeExists($staticPaths, '/@/ecommerce/products'),
                'Ecommerce routes not found'
            );
        }
    }

    private function getActivePlugins()
    {
        $pathData = Env::get('path_data');
        $file = $pathData . '/active_plugins.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        // Fallback: assume all in plugins dir are candidates if no active list? 
        // Or specific ones we know we want to test.
        // For regression, let's look at what's actually active in the test env.
        // But run.php might not have set up active_plugins.json.
        // So let's return a list of "known" plugins we expect to be there.
        return ['McpServer'];
    }

    private function routeExists($paths, $needle)
    {
        return in_array($needle, $paths);
    }
}
