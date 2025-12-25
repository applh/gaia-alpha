<?php

namespace Slides\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class SlidesTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Slides\Controller\SlidesController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}