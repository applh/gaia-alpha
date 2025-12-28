<?php

namespace GaiaAlpha\Tests\Fiber;

use GaiaAlpha\Daemon\Protocol\WebSocket;
use GaiaAlpha\Daemon\Stream;

class MockWsStream extends Stream
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

    public function write(string $data): int
    {
        return strlen($data);
    }
}

class ProtoWebSocketTest
{
    public function testMaskAndUnmask()
    {
        $ws = new WebSocket();
        $payload = "Hello World";
        $mask = "1234"; // 4 bytes

        // Construct a masked frame in memory
        // Fin(1) + Text(1) = 0x81
        // Mask(1) + Len(11) = 11 | 0x80 = 0x8B
        $frame = chr(0x81) . chr(0x8B) . $mask;
        for ($i = 0; $i < strlen($payload); $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }

        $stream = new MockWsStream($frame);

        // No Loop needed for MockStream
        $msg = $ws->read($stream);

        if ($msg === $payload) {
            echo "PASS: WebSocket mask/unmask logic\n";
        } else {
            echo "FAIL: WebSocket mask logic. Expected '$payload', got '$msg'\n";
        }
    }
}

(new ProtoWebSocketTest())->testMaskAndUnmask();
