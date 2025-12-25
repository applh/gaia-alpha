<?php

namespace FormBuilder\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class FormBuilderTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'FormBuilder\Controller\FormController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}