<?php

namespace ScreenRecorder\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class ScreenRecorderTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'ScreenRecorder\Controller\ScreenRecorderController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}