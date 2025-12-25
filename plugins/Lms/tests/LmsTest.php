<?php

namespace Lms\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class LmsTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Lms\LmsController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}