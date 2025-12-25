<?php

namespace MediaLibrary\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class MediaLibraryTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'MediaLibrary\Controller\MediaLibraryController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}