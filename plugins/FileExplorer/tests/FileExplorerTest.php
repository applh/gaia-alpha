<?php

namespace FileExplorer\Tests;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class FileExplorerTest extends TestCase
{
    public function testPluginLoad()
    {
        $className = 'FileExplorer\Controller\FileExplorerController';
        Assert::assertTrue(class_exists($className), "Plugin class $className should exist");
    }
}