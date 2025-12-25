<?php

namespace TestPluginMcp\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class TestPluginMcpTest extends TestCase
{
    public function testPluginStructure()
    {
        $pluginDir = __DIR__ . '/..';
        Assert::assertTrue(file_exists($pluginDir . '/plugin.json'), "plugin.json should exist");
        Assert::assertTrue(file_exists($pluginDir . '/index.php'), "index.php should exist");
    }
}
