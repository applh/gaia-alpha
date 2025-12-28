<?php

require_once __DIR__ . '/../class/GaiaAlpha/App.php';

use GaiaAlpha\App;
use GaiaAlpha\Daemon\Loop;
use GaiaAlpha\Daemon\Socket;
use GaiaAlpha\Daemon\Stream;

// Bootstrap via App::cli_setup
// This registers autoloaders and sets up Env
App::cli_setup(dirname(__DIR__));

$port = 8081; // Use 8081 to avoid conflict with standard 8080 if running

try {
    // 1. WebSocket Server (TCP 8081)
    $server = new Socket($port);
    $server->listen(function (Stream $stream) {
        $peer = stream_socket_get_name($stream->getResource(), true);
        echo "TCP: New connection from $peer\n";

        $http = new \GaiaAlpha\Daemon\Protocol\Http();
        $request = $http->parseRequest($stream);

        if (!$request) {
            echo "TCP: Failed to parse HTTP request from $peer\n";
            $stream->close();
            return;
        }

        $headers = $request['headers'];
        $uri = $request['uri'];
        $method = $request['method'];

        // --- ROUTING LOGIC ---

        // 1. WebSocket Upgrade
        if (isset($headers['upgrade']) && strtolower($headers['upgrade']) === 'websocket') {
            echo "Routing to WebSocket Handshake...\n";
            $ws = new \GaiaAlpha\Daemon\Protocol\WebSocket();
            if (!$ws->handshake($stream, $request)) {
                echo "WS: Handshake failed for $peer\n";
                $stream->close();
                return;
            }

            // WS Loop
            while (true) {
                $message = $ws->read($stream);
                if ($message === null) {
                    echo "WS: Closed $peer\n";
                    break;
                }
                $ws->write($stream, "Echo: " . $message);
            }
            return;
        }

        // 2. SSE Initialization (GET /sse)
        if ($method === 'GET' && strpos($uri, '/sse') === 0) {
            echo "Routing to SSE Init...\n";
            $sse = new \GaiaAlpha\Daemon\Protocol\Sse();
            $http->sendSseHeaders($stream);

            $sessionId = \GaiaAlpha\Daemon\SessionManager::get()->createSession($stream);
            $sse->sendEvent($stream, 'endpoint', ['uri' => "/messages?sessionId=$sessionId"]);

            echo "SSE: Started Session $sessionId for $peer\n";

            // Keep connection open and send pings
            // In a real server, we'd park this Fiber until an event needs to be sent.
            // For now, we just loop and sleep. 
            // TODO: Park fiber in SessionManager
            while (true) {
                \GaiaAlpha\Daemon\Loop::sleep(15);
                // Check if client is still there?
                // Writing to check connectivity
                try {
                    $sse->sendKeepAlive($stream);
                } catch (\Throwable $e) {
                    echo "SSE: Client disconnected $sessionId\n";
                    \GaiaAlpha\Daemon\SessionManager::get()->closeSession($sessionId);
                    break;
                }
            }
            return;
        }

        // 3. MCP Message (POST /messages)
        if ($method === 'POST') {
            echo "Routing to MCP POST...\n";
            // Parse Query Params to find sessionId
            parse_str(parse_url($uri, PHP_URL_QUERY), $queryParams);
            $sessionId = $queryParams['sessionId'] ?? null;

            if (!$sessionId || !($targetStream = \GaiaAlpha\Daemon\SessionManager::get()->getSession($sessionId))) {
                $http->sendResponse($stream, 404, "Session Not Found");
                $stream->close();
                return;
            }

            // Read Body
            $len = (int) ($headers['content-length'] ?? 0);
            $body = '';
            if ($len > 0) {
                $body = $stream->read($len);
            }

            $json = json_decode($body, true);
            echo "MCP POST: Received " . substr($body, 0, 50) . "... for session $sessionId\n";

            // Forward to SSE Client as an event
            $sse = new \GaiaAlpha\Daemon\Protocol\Sse();
            $sse->sendEvent($targetStream, 'message', $json);

            $http->sendResponse($stream, 202, "Accepted");
            $stream->close();
            return;
        }

        // 4. Fallback 404
        $http->sendResponse($stream, 404, "Not Found");
        $stream->close();
    });

    // 2. MCP Server (STDIN/STDOUT)
    $mcpIn = new Stream(STDIN);
    $mcpOut = new Stream(STDOUT);

    // We run the MCP loop in a separate Fiber
    Loop::get()->async(function () use ($mcpIn, $mcpOut) {
        $protocol = new \GaiaAlpha\Daemon\Protocol\Mcp();
        // fprintf(STDERR, "MCP: Listening on STDIN...\n"); // Debug log to stderr to avoid corrupting stdout JSON

        while (true) {
            $request = $protocol->read($mcpIn);
            if ($request === null)
                break; // EOF
            if (empty($request))
                continue;

            // Basic JSON-RPC Handling
            $id = $request['id'] ?? null;
            $method = $request['method'] ?? '';

            if ($method === 'ping') {
                $response = $protocol->createResponse($id, 'pong');
                $protocol->write($mcpOut, $response);
            } else {
                // Unknown method
                $response = $protocol->createError($id, -32601, "Method not found: $method");
                $protocol->write($mcpOut, $response);
            }
        }
    });

    echo "Starting Hybrid Fiber Server (WS: $port, MCP: Stdio)...\n";
    Loop::get()->run();

} catch (Throwable $e) {
    fprintf(STDERR, "Fatal Error: %s\n", $e->getMessage());
    exit(1);
}
