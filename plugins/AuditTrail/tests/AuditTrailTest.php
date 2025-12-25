<?php

namespace AuditTrail\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class AuditTrailTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'AuditTrail\AuditController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}