<?php

namespace Ecommerce\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class EcommerceTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'Ecommerce\EcommerceController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}