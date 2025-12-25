<?php

namespace ApiAnalytics\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class ApiAnalyticsTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'ApiAnalytics\Controller\ApiStatsController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}