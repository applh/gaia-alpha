<?php

namespace Chat\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class ChatTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Chat\Controller\ChatController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}