<?php

namespace DatabaseConnections\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class DatabaseConnectionsTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'DatabaseConnections\Controller\ConnectionController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}