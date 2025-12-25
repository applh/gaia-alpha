<?php

namespace NodeEditor\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class NodeEditorTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'NodeEditor\Controller\NodeEditorController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}