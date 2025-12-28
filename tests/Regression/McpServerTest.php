<?php

namespace GaiaAlpha\Tests\Regression;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class McpServerTest extends TestCase
{
    public function testMcpServerStartupAndPing()
    {
        $serverScript = realpath(__DIR__ . '/../../server/start.php');

        Assert::assertTrue(file_exists($serverScript), "Server script not found: $serverScript");

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        ];

        $process = proc_open("php " . escapeshellarg($serverScript), $descriptorspec, $pipes);

        if (!is_resource($process)) {
            Assert::fail("Failed to start server process");
        }

        // Send JSON-RPC Ping
        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'ping',
            'id' => 1
        ]) . "\n";

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        // Set pipes to non-blocking
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        // Wait a bit for processing
        sleep(2);

        // Read Output (Available buffer)
        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);

        // Terminate the process (as it is an infinite loop)
        proc_terminate($process);
        proc_close($process);

        // Assertion 1: Clean STDOUT
        // STDOUT should contain the pong response AND NOTHING ELSE.
        Assert::assertFalse(strpos($output, 'Starting Hybrid Fiber Server') !== false, "STDOUT is polluted with startup logs: $output");
        Assert::assertFalse(strpos($output, 'TCP: New connection') !== false, "STDOUT is polluted with connection logs: $output");

        $output = trim($output);
        $json = json_decode($output, true);

        Assert::assertTrue(json_last_error() === JSON_ERROR_NONE, "Output was not valid JSON. Content: <$output>");
        Assert::assertEquals('pong', $json['result'] ?? null, "Result should be pong");
        Assert::assertEquals(1, $json['id'] ?? null, "ID should be 1");

        // Assertion 2: Logs in STDERR
        Assert::assertStringContains('Starting Hybrid Fiber Server', $errorOutput, "Startup log missing from STDERR");
    }

}
