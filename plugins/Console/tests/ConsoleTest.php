<?php

namespace Console\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class ConsoleTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Console\Controller\ConsoleController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}