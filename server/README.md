# Gaia Alpha Fiber Server

This directory contains the entry point for the Gaia Alpha Fiber Server.

## Starting the Server
Run from the project root:
```bash
php server/start.php
```

## Features
-   **WebSocket Server**: Listens on port 8081 (TCP).
-   **SSE Server**: Listens on port 8081 (HTTP GET /sse).
-   **MCP Server**: Listens on STDIN.
-   **Concurrent**: Uses PHP Fibers and `stream_select` for non-blocking I/O.
-   **Zero-Dependency**: No external libraries (ReactPHP/Amp) used.


## Running with Docker

You can build and run the server using the provided Dockerfile.

### Build
```bash
docker build -f server/Dockerfile -t gaia-fiber-server .
```

### Run
```bash
# Run interactively (to see log output)
docker run -it -p 8081:8081 -i gaia-fiber-server

# Run as daemon
docker run -d -p 8081:8081 --name gaia-fiber gaia-fiber-server
```

### Protocol Usage
-   **WebSocket**: Connect to `ws://localhost:8081`
-   **SSE**: Connect to `http://localhost:8081/sse`
-   **MCP (Stdio)**: When running interactively (`-i`), standard input is piped to the MCP handler.
    ```bash
    echo '{"jsonrpc": "2.0", "id": 1, "method": "ping"}' | docker run -i gaia-fiber-server
    ```

## Configuration
Port and settings are currently defined in `start.php`.
```php
$port = 8081;
```

## Testing
You can test the WebSocket connection using `client_test.php` (if available) or any standard WebSocket client.
You can test MCP by piping JSON-RPC to stdin:
```bash
echo '{"jsonrpc": "2.0", "id": 1, "method": "ping"}' | php server/start.php
```
