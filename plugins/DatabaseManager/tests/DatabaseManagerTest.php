<?php

namespace DatabaseManager\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class DatabaseManagerTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'DatabaseManager\Controller\DatabaseController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}