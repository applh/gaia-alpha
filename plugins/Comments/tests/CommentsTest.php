<?php

namespace Comments\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class CommentsTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Comments\Comment';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}