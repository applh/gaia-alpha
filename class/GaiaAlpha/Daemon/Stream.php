<?php

namespace GaiaAlpha\Daemon;

class Stream
{
    private $resource;
    private int $bufferSize = 8192;

    public function __construct($resource)
    {
        $this->resource = $resource;
        stream_set_blocking($this->resource, false);
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function read(?int $length = null): ?string
    {
        Loop::awaitReadable($this->resource);

        $length = $length ?? $this->bufferSize;
        $data = fread($this->resource, $length);

        if ($data === false || $data === '') {
            return null; // EOF or Error
        }

        return $data;
    }

    public function readLine(int $maxLength = 8192): ?string
    {
        Loop::awaitReadable($this->resource);

        // Note: fgets on non-blocking stream might return partial line
        // A robust implementation needs buffering. For Phase 1 simple echo, this acts as a basic wrapper.
        // We will improve this for MCP JSON-RPC.
        $data = fgets($this->resource, $maxLength);

        if ($data === false) {
            return null;
        }

        return $data;
    }

    public function write(string $data): int
    {
        // Wait until we can write
        Loop::awaitWritable($this->resource);

        $bytes = fwrite($this->resource, $data);
        return $bytes ?: 0;
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
