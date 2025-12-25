<?php

namespace ComponentBuilder\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class ComponentBuilderTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'ComponentBuilder\Controller\ComponentBuilderController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}