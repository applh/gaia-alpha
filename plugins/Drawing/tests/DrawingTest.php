<?php

namespace Drawing\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class DrawingTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Drawing\Controller\DrawingController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}