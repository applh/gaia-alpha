<?php

namespace GaiaAlpha\Tests\Framework;

require_once __DIR__ . '/Assert.php';
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/ApiTestCase.php';

class TestRunner
{

    private $passed = 0;
    private $failed = 0;
    private $errors = 0;

    public function run($directories)
    {
        if (is_string($directories)) {
            $directories = [$directories];
        }

        $files = [];
        foreach ($directories as $dir) {
            $files = array_merge($files, $this->scanDirectory($dir));
        }

        echo "Gaia Alpha Test Runner\n";
        echo "Running tests in: " . implode(', ', $directories) . "\n\n";

        foreach ($files as $file) {
            $this->runTestFile($file);
        }

        echo "\n-------------------------------------------------\n";
        echo "Summary: ";
        echo "\033[32mPassed: $this->passed\033[0m, ";
        echo "\033[31mFailed: $this->failed\033[0m, ";
        echo "\033[33mErrors: $this->errors\033[0m\n";

        if ($this->failed > 0 || $this->errors > 0) {
            exit(1);
        }
    }

    private function scanDirectory($dir)
    {
        $results = [];
        $files = scandir($dir);
        foreach ($files as $value) {
            if ($value === '.' || $value === '..')
                continue;

            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                if (strpos($value, 'Test.php') !== false) { // Match *Test.php
                    $results[] = $path;
                }
            } else if ($value != 'framework') { // Don't scan framework dir itself
                $results = array_merge($results, $this->scanDirectory($path));
            }
        }
        return $results;
    }

    private function runTestFile($file)
    {
        require_once $file;

        // Find classes in file
        $content = file_get_contents($file);
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];

            // Handle Namespaces if present in file content regex (simplified)
            if (preg_match('/namespace\s+([\w\\\]+);/', $content, $nsMatches)) {
                $className = $nsMatches[1] . '\\' . $className;
            }

            if (class_exists($className)) {
                $reflector = new \ReflectionClass($className);
                if ($reflector->isSubclassOf(TestCase::class)) {
                    $this->runTestClass($className);
                }
            }
        }
    }

    private function runTestClass($className)
    {
        $test = new $className();
        $methods = get_class_methods($className);

        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                // It's a test method
                try {
                    $test->setUp();
                    $test->$method();
                    $test->tearDown();

                    echo "."; // Progress dot
                    $this->passed++;
                } catch (AssertionFailedException $e) {
                    echo "F";
                    $this->failed++;
                    echo "\n\033[31mFailure in $className::$method\033[0m\n";
                    echo "  " . $e->getMessage() . "\n";
                } catch (\Throwable $e) {
                    echo "E";
                    $this->errors++;
                    echo "\n\033[33mError in $className::$method\033[0m\n";
                    echo "  " . $e->getMessage() . "\n";
                }
            }
        }
    }
}
