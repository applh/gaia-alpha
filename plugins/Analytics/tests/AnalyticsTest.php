<?php

namespace Analytics\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class AnalyticsTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Analytics\Controller\AnalyticsController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}