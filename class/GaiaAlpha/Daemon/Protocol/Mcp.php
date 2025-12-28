<?php

namespace GaiaAlpha\Daemon\Protocol;

use GaiaAlpha\Daemon\Stream;

class Mcp
{
    /**
     * Read a JSON-RPC message from Stdin.
     * Expects newline delimited JSON (JSON-L).
     */
    public function read(Stream $stream): ?array
    {
        $line = $stream->readLine();
        if ($line === null) {
            return null;
        }

        $trimmed = trim($line);
        if ($trimmed === '') {
            return []; // Empty line keep-alive?
        }

        return json_decode($trimmed, true);
    }

    /**
     * Write a JSON-RPC response to Stdout.
     */
    public function write(Stream $stream, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stream->write($json . "\n");
    }

    public function createResponse($id, $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ];
    }

    public function createError($id, $code, $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }
}
