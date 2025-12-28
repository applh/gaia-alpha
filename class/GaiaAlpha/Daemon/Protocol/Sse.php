<?php

namespace GaiaAlpha\Daemon\Protocol;

use GaiaAlpha\Daemon\Stream;

class Sse
{
    public function sendEvent(Stream $stream, string $event, array $data): void
    {
        $payload = "event: $event\n";
        $payload .= "data: " . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";

        $stream->write($payload);
    }

    public function sendKeepAlive(Stream $stream): void
    {
        $stream->write(": keep-alive\n\n");
    }
}
