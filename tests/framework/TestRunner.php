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
        $files = [];
        foreach ($directories as $target) {
            if (is_file($target)) {
                $files[] = realpath($target);
            } elseif (is_dir($target)) {
                $files = array_merge($files, $this->scanDirectory($target));
            }
        }

        echo "Gaia Alpha Test Runner\n";
        echo "Running tests in: " . implode(', ', $directories) . "\n\n";

        foreach ($files as $file) {
            $this->runTestFile($file);
        }

        $hasColor = function_exists('stream_isatty') && stream_isatty(STDOUT);
        $green = $hasColor ? "\033[32m" : "";
        $red = $hasColor ? "\033[31m" : "";
        $yellow = $hasColor ? "\033[33m" : "";
        $reset = $hasColor ? "\033[0m" : "";

        echo "\n-------------------------------------------------\n";
        echo "Summary: ";
        echo "{$green}Passed: $this->passed{$reset}, ";
        echo "{$red}Failed: $this->failed{$reset}, ";
        echo "{$yellow}Errors: $this->errors{$reset}\n";

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
                    $color = (function_exists('stream_isatty') && stream_isatty(STDOUT)) ? "\033[31m" : "";
                    $reset = (function_exists('stream_isatty') && stream_isatty(STDOUT)) ? "\033[0m" : "";
                    echo "\n{$color}Failure in $className::$method{$reset}\n";
                    echo "  " . $e->getMessage() . "\n";
                } catch (\Throwable $e) {
                    echo "E";
                    $this->errors++;
                    $color = (function_exists('stream_isatty') && stream_isatty(STDOUT)) ? "\033[33m" : "";
                    $reset = (function_exists('stream_isatty') && stream_isatty(STDOUT)) ? "\033[0m" : "";
                    echo "\n{$color}Error in $className::$method{$reset}\n";
                    echo "  " . $e->getMessage() . "\n";
                }
            }
        }
    }
}
