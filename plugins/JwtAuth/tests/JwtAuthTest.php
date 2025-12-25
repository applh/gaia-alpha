<?php

namespace JwtAuth\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class JwtAuthTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'JwtAuth\Cli\JwtCommands';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}