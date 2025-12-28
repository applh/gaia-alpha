<?php

namespace GaiaAlpha\Daemon\Protocol;

use GaiaAlpha\Daemon\Stream;

class Http
{
    /**
     * Read the HTTP Request Line and Headers.
     * Returns an associative array with 'method', 'uri', 'protocol', and 'headers'.
     * Returns null if connection is closed or invalid.
     */
    public function parseRequest(Stream $stream): ?array
    {
        $requestLine = $stream->readLine();
        if ($requestLine === null || trim($requestLine) === '') {
            return null;
        }

        // Parse Request Line: GET /uri HTTP/1.1
        $parts = explode(' ', trim($requestLine));
        if (count($parts) < 3) {
            return null;
        }

        $method = $parts[0];
        $uri = $parts[1];
        $protocol = $parts[2];
        $headers = [];

        // Parse Headers
        while (true) {
            $line = $stream->readLine();
            if ($line === null || $line === "\r\n" || $line === "\n") {
                break;
            }

            $line = trim($line);
            if ($line === '')
                break;

            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        return [
            'method' => $method,
            'uri' => $uri,
            'protocol' => $protocol,
            'headers' => $headers
        ];
    }

    public static function sendResponse(Stream $stream, int $code, string $reason, array $headers = [], string $body = ''): void
    {
        $response = "HTTP/1.1 $code $reason\r\n";
        foreach ($headers as $key => $value) {
            $response .= "$key: $value\r\n";
        }
        $response .= "Content-Length: " . strlen($body) . "\r\n";
        $response .= "\r\n";
        $response .= $body;

        $stream->write($response);
    }

    public static function sendSseHeaders(Stream $stream): void
    {
        $response = "HTTP/1.1 200 OK\r\n" .
            "Content-Type: text/event-stream\r\n" .
            "Cache-Control: no-cache\r\n" .
            "Connection: keep-alive\r\n" .
            "Access-Control-Allow-Origin: *\r\n" .
            "\r\n";
        $stream->write($response);
    }
}
