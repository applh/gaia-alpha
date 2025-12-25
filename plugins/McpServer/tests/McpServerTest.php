<?php

namespace McpServer\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class McpServerTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'McpServer\Cli\McpCommands';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}