<?php

namespace GraphsManagement\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class GraphsManagementTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'GraphsManagement\Controller\GraphController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}