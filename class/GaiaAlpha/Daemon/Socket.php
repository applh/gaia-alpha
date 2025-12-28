<?php

namespace GaiaAlpha\Daemon;

use RuntimeException;
use Throwable;

class Socket
{
    private $resource;
    private int $port;
    private bool $running = true;

    public function __construct(int $port = 8080)
    {
        $this->port = $port;
        $context = stream_context_create([
            'socket' => [
                'so_reuseport' => 1,
                'so_reuseaddr' => 1,
            ]
        ]);

        $this->resource = stream_socket_server("tcp://0.0.0.0:$port", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

        if (!$this->resource) {
            throw new RuntimeException("Could not bind to port $port: $errstr");
        }

        stream_set_blocking($this->resource, false);
    }

    public function listen(callable $onConnect): void
    {
        // Start a fiber that monitors accept
        Loop::get()->async(function () use ($onConnect) {
            echo "Listening on port {$this->port}...\n";

            while ($this->running) {
                Loop::awaitReadable($this->resource);

                // Keep accepting connections
                try {
                    $client = @stream_socket_accept($this->resource, 0); // Non-blocking accept check
                    if ($client) {
                        $stream = new Stream($client);

                        // Spawn a new Fiber for this client connection
                        Loop::get()->async(function () use ($onConnect, $stream) {
                            try {
                                $onConnect($stream);
                            } catch (Throwable $e) {
                                echo "Client Error: " . $e->getMessage() . "\n";
                                $stream->close();
                            }
                        });
                    }
                } catch (Throwable $e) {
                    echo "Accept Error: " . $e->getMessage() . "\n";
                }
            }
        });
    }

    public function stop(): void
    {
        $this->running = false;
    }
}
