<?php

namespace SocialNetworks\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class SocialNetworksTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'SocialNetworks\Controller\SocialNetworksController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}