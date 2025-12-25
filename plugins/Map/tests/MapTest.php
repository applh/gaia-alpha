<?php

namespace Map\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class MapTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Map\Controller\MapController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}