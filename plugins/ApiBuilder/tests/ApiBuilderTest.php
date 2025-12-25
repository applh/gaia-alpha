<?php

namespace ApiBuilder\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class ApiBuilderTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'ApiBuilder\Controller\ApiBuilderController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}