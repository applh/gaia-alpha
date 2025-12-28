# Custom Fiber Server Architecture

## Overview
Gaia Alpha now includes a custom, zero-dependency concurrent server implementation located in `server/` and `class/GaiaAlpha/Daemon`.
This server is designed to handle **WebSocket** connections (TCP) and **MCP** requests (Stdio) concurrently within a single PHP process using **Fibers**.

## Components

### 1. Event Loop (`Loop.php`)
A wrapper around native `stream_select()`. It manages a list of readable and writable streams and executes callbacks when I/O is ready.
-   **Singleton**: Accessed via `Loop::get()`.
-   **Async**: Supports `Loop::get()->async(callable)` to spawn new Fibers.
-   **Await**: `Loop::awaitReadable($stream)` suspends the current Fiber until the stream is ready.

### 2. Scheduler (`Scheduler.php`)
Manages the execution queue of Fibers.
-   Run queue: New or resumed fibers.
-   Handles `Fiber::suspend()` and `Fiber::resume()`.

### 3. Streams (`Stream.php`)
A wrapper around PHP stream resources to enforce non-blocking mode and provide `read()`/`write()` methods that automatically yield execution to the Loop if I/O would block.

### 4. Protocols
-   **WebSocket (`Protocol/WebSocket.php`)**: Implements RFC 6455 Handshake and Framing. Supports text messages (masked client->server, unmasked server->client).
-   **MCP (`Protocol/Mcp.php`)**: Implements JSON-RPC 2.0 over Stdio (Newline Delimited JSON).

## Usage
The server entry point is `server/start.php`.

```bash
php server/start.php
```

It listens on:
-   **TCP Port 8081**: For WebSocket connections.
-   **STDIN**: For MCP JSON-RPC requests.

## Hybrid Concurrency
The server runs a single Event Loop that checks both the TCP socket and STDIN stream.
-   When a WebSocket client connects, a new Fiber is spawned for that connection.
-   When MCP input arrives on STDIN, the MCP Fiber processes it.
-   Long-running operations in one protocol do not block the other (provided they use async I/O or yield properly).

## Deployment

### Docker
The server can be deployed as a container using `server/Dockerfile`.

1.  **Build**:
    ```bash
    docker build -f server/Dockerfile -t gaia-fiber-server .
    ```

2.  **Run**:
    ```bash
    docker run -d -p 8081:8081 --name gaia-fiber gaia-fiber-server
    ```

### Systemd / Supervisor
For bare-metal deployment, use a process manager to keep `php server/start.php` alive.

Example Supervisor Config:
```ini
[program:gaia-fiber]
command=php /path/to/gaia-alpha/server/start.php
autostart=true
autorestart=true
stderr_logfile=/var/log/gaia-fiber.err.log
stdout_logfile=/var/log/gaia-fiber.out.log
```
