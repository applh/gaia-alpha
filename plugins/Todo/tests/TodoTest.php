<?php

namespace Todo\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class TodoTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Todo\Controller\TodoController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}