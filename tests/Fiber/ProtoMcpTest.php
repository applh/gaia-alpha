<?php

namespace GaiaAlpha\Tests\Fiber;

use GaiaAlpha\Daemon\Protocol\Mcp;
use GaiaAlpha\Daemon\Stream;

class MockMcpStream extends Stream
{
    private string $buffer;

    public function __construct(string $content)
    {
        $this->buffer = $content;
    }

    public function read(?int $length = null): ?string
    {
        if ($this->buffer === '')
            return null;

        $length = $length ?? strlen($this->buffer);
        $chunk = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, strlen($chunk));
        return $chunk;
    }

    public function readLine(int $maxLength = 8192): ?string
    {
        if ($this->buffer === '')
            return null;

        $pos = strpos($this->buffer, "\n");
        if ($pos === false) {
            $line = $this->buffer;
            $this->buffer = '';
            return $line;
        }

        $line = substr($this->buffer, 0, $pos + 1);
        $this->buffer = substr($this->buffer, $pos + 1);
        return $line;
    }

    public function write(string $data): int
    {
        echo $data; // For debugging or capturing if needed
        return strlen($data);
    }
}

class ProtoMcpTest
{
    public function testReadJsonRpc()
    {
        $mcp = new Mcp();
        $input = "{\"jsonrpc\": \"2.0\", \"method\": \"ping\"}\n";

        // Use MockStream to avoid Loop/stream_select complexity in unit test
        $stream = new MockMcpStream($input);

        $req = $mcp->read($stream);

        if ($req['method'] === 'ping') {
            echo "PASS: MCP Read JSON-RPC\n";
        } else {
            echo "FAIL: MCP Read. Got " . json_encode($req) . "\n";
        }
    }
}

(new ProtoMcpTest())->testReadJsonRpc();
