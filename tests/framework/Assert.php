<?php

namespace GaiaAlpha\Tests\Framework;

class Assert
{

    public static function assertTrue($condition, $message = '')
    {
        if ($condition !== true) {
            throw new AssertionFailedException($message ?: "Failed asserting that condition is true.");
        }
    }

    public static function assertFalse($condition, $message = '')
    {
        if ($condition !== false) {
            throw new AssertionFailedException($message ?: "Failed asserting that condition is false.");
        }
    }

    public static function assertEquals($expected, $actual, $message = '')
    {
        if ($expected != $actual) {
            $typeEx = gettype($expected);
            $typeAct = gettype($actual);
            $valEx = var_export($expected, true);
            $valAct = var_export($actual, true);
            throw new AssertionFailedException($message ?: "Failed asserting that $valAct ($typeAct) matches expected $valEx ($typeEx).");
        }
    }

    public static function assertNotEquals($expected, $actual, $message = '')
    {
        if ($expected == $actual) {
            throw new AssertionFailedException($message ?: "Failed asserting that two variables are not equal.");
        }
    }

    public static function assertCount($expectedCount, $haystack, $message = '')
    {
        $count = count($haystack);
        if ($count !== $expectedCount) {
            throw new AssertionFailedException($message ?: "Failed asserting that array count $count matches expected $expectedCount.");
        }
    }

    public static function assertArrayHasKey($key, $array, $message = '')
    {
        if (!array_key_exists($key, $array)) {
            throw new AssertionFailedException($message ?: "Failed asserting that array has key '$key'.");
        }
    }

    public static function assertStringContains($needle, $haystack, $message = '')
    {
        if (strpos($haystack, $needle) === false) {
            throw new AssertionFailedException($message ?: "Failed asserting that string contains '$needle'.");
        }
    }

    public static function fail($message = '')
    {
        throw new AssertionFailedException($message ?: "Test failed intentionally.");
    }
}

class AssertionFailedException extends \Exception
{
}
