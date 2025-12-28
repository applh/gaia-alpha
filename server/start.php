<?php

require_once __DIR__ . '/../class/GaiaAlpha/App.php';

use GaiaAlpha\App;
use GaiaAlpha\Daemon\Loop;
use GaiaAlpha\Daemon\Socket;
use GaiaAlpha\Daemon\Stream;

// Bootstrap via App::cli_setup
// This registers autoloaders and sets up Env
App::cli_setup(dirname(__DIR__));

// Parse Arguments
$options = getopt("", ["port::", "modes::"]);
$port = (int) ($options['port'] ?? 8081);
$modesStr = $options['modes'] ?? 'stdio,socket';
$modes = array_map('trim', explode(',', $modesStr));

$enableSocket = in_array('socket', $modes);
$enableStdio = in_array('stdio', $modes);

try {
    // 1. WebSocket Server (TCP)
    if ($enableSocket) {
        $server = new Socket($port);
        $server->listen(function (Stream $stream) {
            $peer = stream_socket_get_name($stream->getResource(), true);
            fprintf(STDERR, "TCP: New connection from $peer\n");

            $http = new \GaiaAlpha\Daemon\Protocol\Http();
            $request = $http->parseRequest($stream);

            if (!$request) {
                fprintf(STDERR, "TCP: Failed to parse HTTP request from $peer\n");
                $stream->close();
                return;
            }

            $headers = $request['headers'];
            $uri = $request['uri'];
            $method = $request['method'];

            // --- ROUTING LOGIC ---

            // 1. WebSocket Upgrade
            if (isset($headers['upgrade']) && strtolower($headers['upgrade']) === 'websocket') {
                fprintf(STDERR, "Routing to WebSocket Handshake...\n");
                $ws = new \GaiaAlpha\Daemon\Protocol\WebSocket();
                if (!$ws->handshake($stream, $request)) {
                    fprintf(STDERR, "WS: Handshake failed for $peer\n");
                    $stream->close();
                    return;
                }

                // WS Loop
                while (true) {
                    $message = $ws->read($stream);
                    if ($message === null) {
                        fprintf(STDERR, "WS: Closed $peer\n");
                        break;
                    }
                    $ws->write($stream, "Echo: " . $message);
                }
                return;
            }

            // 2. SSE Initialization (GET /sse)
            if ($method === 'GET' && strpos($uri, '/sse') === 0) {
                fprintf(STDERR, "Routing to SSE Init...\n");
                $sse = new \GaiaAlpha\Daemon\Protocol\Sse();
                $http->sendSseHeaders($stream);

                $sessionId = \GaiaAlpha\Daemon\SessionManager::get()->createSession($stream);
                $sse->sendEvent($stream, 'endpoint', ['uri' => "/messages?sessionId=$sessionId"]);

                fprintf(STDERR, "SSE: Started Session $sessionId for $peer\n");

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
                        fprintf(STDERR, "SSE: Client disconnected $sessionId\n");
                        \GaiaAlpha\Daemon\SessionManager::get()->closeSession($sessionId);
                        break;
                    }
                }
                return;
            }

            // 3. MCP Message (POST /messages)
            if ($method === 'POST') {
                fprintf(STDERR, "Routing to MCP POST...\n");
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
                fprintf(STDERR, "MCP POST: Received " . substr($body, 0, 50) . "... for session $sessionId\n");

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
    }

    // 2. MCP Server (STDIN/STDOUT)
    if ($enableStdio) {
        $mcpIn = new Stream(STDIN);
        $mcpOut = new Stream(STDOUT);

        // We run the MCP loop in a separate Fiber
        Loop::get()->async(function () use ($mcpIn, $mcpOut) {
            // Use the existing robust McpServer definition from the plugin
            // This handles initialize, tools/list, tools/call etc.
            $mcpServer = new \McpServer\Server(STDIN, STDOUT);

            // We need to manually drive the loop because we are inside a Fiber
            // and McpServer::runStdio() is a blocking while loop on fgets() which might block the whole process
            // if not careful. However, pure PHP fgets() on STDIN might block key inputs.
            // BUT, since we are in a Fiber architecture, we should ideally use non-blocking reads.

            // ADAPTER: We will read from our Stream wrapper (which yields) and pass to McpServer
            // We will instantiate McpServer but bypass its runStdio() loop.

            $protocol = new \GaiaAlpha\Daemon\Protocol\Mcp();

            while (true) {
                $request = $protocol->read($mcpIn); // Yields until line available
                if ($request === null)
                    break; // EOF
                if (empty($request))
                    continue;

                // Delegate to McpServer
                // We pass null as sessionId for now (Stdio is one session)
                $response = $mcpServer->handleRequestPublic($request, 'stdio');

                if ($response) {
                    // Write back using our async-friendly stream
                    $protocol->write($mcpOut, $response);
                }
            }
        });
    }

    $activeProtocols = [];
    if ($enableSocket)
        $activeProtocols[] = "WS/SSE: $port";
    if ($enableStdio)
        $activeProtocols[] = "MCP: Stdio";

    fprintf(STDERR, "Starting Hybrid Fiber Server (%s)...\n", implode(', ', $activeProtocols));
    Loop::get()->run();

} catch (Throwable $e) {
    fprintf(STDERR, "Fatal Error: %s\n", $e->getMessage());
    exit(1);
}
