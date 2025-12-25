<?php

namespace MlPca\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class MlPcaTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'MlPca\Controller\PcaController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}