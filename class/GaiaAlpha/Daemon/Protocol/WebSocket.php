<?php

namespace GaiaAlpha\Daemon\Protocol;

use GaiaAlpha\Daemon\Stream;
use RuntimeException;

class WebSocket
{
    private const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * Perform the WebSocket Handshake on a new connection.
     * Request must be already parsed by Http::parseRequest.
     */
    public function handshake(Stream $stream, array $request): bool
    {
        $headers = $request['headers'] ?? [];

        // Validate required headers
        if (!isset($headers['sec-websocket-key'])) {
            Http::sendResponse($stream, 400, "Bad Request", [], "Missing Sec-WebSocket-Key");
            return false;
        }

        $key = $headers['sec-websocket-key'];

        // Generate Accept Key
        $acceptKey = base64_encode(sha1($key . self::GUID, true));

        // Send Response
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $acceptKey\r\n" .
            "\r\n";

        $stream->write($response);

        return true;
    }

    /**
     * Write a text frame to the client.
     */
    public function write(Stream $stream, string $payload): void
    {
        $length = strlen($payload);
        $header = chr(0x81); // Fin + Text Opcode (0x1)

        if ($length <= 125) {
            $header .= chr($length);
        } elseif ($length <= 65535) {
            $header .= chr(126) . pack('n', $length);
        } else {
            $header .= chr(127) . pack('J', $length);
        }

        $stream->write($header . $payload);
    }

    /**
     * Read a message from the client.
     * Returns the payload string or null if connection closed.
     */
    public function read(Stream $stream): ?string
    {
        // 1. Read first byte (Fin + Opcode)
        $byte1 = $stream->read(1);
        if ($byte1 === null)
            return null;

        $b1 = ord($byte1);
        $fin = ($b1 & 0x80) !== 0;
        $opcode = $b1 & 0x0F;

        if ($opcode === 0x8) { // Close frame
            return null;
        }

        // 2. Read second byte (Mask + Length)
        $byte2 = $stream->read(1);
        if ($byte2 === null)
            return null;

        $b2 = ord($byte2);
        $isMasked = ($b2 & 0x80) !== 0;
        $payloadLen = $b2 & 0x7F;

        // 3. Handle extended length
        if ($payloadLen === 126) {
            $data = $stream->read(2);
            if ($data === null)
                return null;
            $payloadLen = unpack('n', $data)[1];
        } elseif ($payloadLen === 127) {
            $data = $stream->read(8);
            if ($data === null)
                return null;
            // PHP doesnt have unsigned 64bit int unpacked easily, assume it fits in signed
            $payloadLen = unpack('J', $data)[1];
        }

        // 4. Read Masking Key (Client -> Server messages MUST be masked)
        $maskKey = '';
        if ($isMasked) {
            $maskKey = $stream->read(4);
            if ($maskKey === null)
                return null;
        }

        // 5. Read Payload
        if ($payloadLen > 0) {
            $payload = '';
            // Read until complete
            $left = $payloadLen;
            while ($left > 0) {
                $chunk = $stream->read($left);
                if ($chunk === null)
                    return null;
                $payload .= $chunk;
                $left -= strlen($chunk);
            }
        } else {
            $payload = '';
        }

        // 6. Unmask
        if ($isMasked) {
            $payload = $this->unmask($payload, $maskKey);
        }

        return $payload;
    }

    private function unmask(string $payload, string $maskKey): string
    {
        $unmasked = '';
        $len = strlen($payload);
        for ($i = 0; $i < $len; $i++) {
            $unmasked .= $payload[$i] ^ $maskKey[$i % 4];
        }
        return $unmasked;
    }
}
