<?php

namespace Mail\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class MailTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Mail\Controller\MailController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}