<?php

namespace GaiaAlpha\Tests\Regression;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class CliTest extends TestCase
{
    public function testCliHelpOutput()
    {
        $rootDir = __DIR__ . '/../../';
        $command = 'php ' . $rootDir . 'cli.php help';

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        $outputStr = implode("\n", $output);

        Assert::assertEquals(0, $exitCode, "CLI should exit with code 0");
        Assert::assertStringContains('Usage: php cli.php', $outputStr, "Output should contain usage info");
        Assert::assertStringContains('Commands:', $outputStr, "Output should list commands");
    }
}
