<?php

namespace MultiSite\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class MultiSiteTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'MultiSite\SiteController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}