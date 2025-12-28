<?php

namespace GaiaAlpha\Tests\Regression;

use GaiaAlpha\Tests\Framework\ApiTestCase;
use GaiaAlpha\Tests\Framework\Assert;
use GaiaAlpha\Env;
use GaiaAlpha\Database;

class InstallTest extends ApiTestCase
{
    private $originalPathData;
    private $testDataPath;

    public function setUp()
    {
        parent::setUp();

        $this->originalPathData = Env::get('path_data');

        // Define a safe test data path
        $this->testDataPath = Env::get('root_dir') . '/tests/my-data-test';

        // Ensure we start clean
        $this->cleanup();

        // Override path_data to use our test directory
        Env::set('path_data', $this->testDataPath);
    }

    public function tearDown()
    {
        // Cleanup after test
        $this->cleanup();

        // Restore Env
        if ($this->originalPathData) {
            Env::set('path_data', $this->originalPathData);
        }

        // Reset DB Connection to avoid stale connection in next test
        \GaiaAlpha\Model\DB::setConnection(null);

        parent::tearDown();
    }

    private function cleanup()
    {
        if (is_dir($this->testDataPath)) {
            // Simple recursive delete
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

    public function testInstallProcedure()
    {
        // 1. Prepare Data
        $data = [
            'username' => 'admin_test',
            'password' => 'secret123',
            'site_title' => 'Test Site',
            'db_type' => 'sqlite'
        ];

        // 2. Mock Request Input
        \GaiaAlpha\Request::mock($data);

        // 3. Create Install Controller and Execute
        $controller = new \GaiaAlpha\Controller\InstallController();

        // Capture Output
        ob_start();
        // Use @ to suppress "headers already sent" warnings during test
        @$controller->install();
        $output = ob_get_clean();

        // 4. Verify Response
        // Clean output in case warnings slipped through (though @ should catch them)
        // Find JSON start
        $jsonStart = strpos($output, '{');
        if ($jsonStart !== false) {
            $output = substr($output, $jsonStart);
        }

        $json = json_decode($output, true);

        Assert::assertTrue(isset($json['success']) && $json['success'] === true, "Install failed: " . $output);

        // 5. Verify Files Created
        Assert::assertTrue(file_exists($this->testDataPath . '/config.php'), 'config.php not created');
        Assert::assertTrue(file_exists($this->testDataPath . '/database.sqlite'), 'database.sqlite not created');
        Assert::assertTrue(file_exists($this->testDataPath . '/installed.lock'), 'installed.lock not created');

        // 6. Verify Database Content
        // Connect to the NEW sqlite db
        $dsn = 'sqlite:' . $this->testDataPath . '/database.sqlite';
        $db = new \PDO($dsn);

        $stmt = $db->query("SELECT * FROM users WHERE username = 'admin_test'");
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        Assert::assertTrue((bool) $user, 'Admin user not found in database');
        Assert::assertEquals('admin_test', $user['username']);
        Assert::assertEquals(100, $user['level']);
    }
}
