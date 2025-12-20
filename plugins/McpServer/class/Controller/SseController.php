<?php

namespace McpServer\Controller;

use McpServer\Server;
use McpServer\SessionManager;
use GaiaAlpha\Response;
use GaiaAlpha\Request;

class SseController
{
    private $maxDuration = 300; // 5 minutes
    private $heartbeatInterval = 15; // 15 seconds

    /**
     * Create a new MCP session
     * POST /@/mcp/session
     */
    public function createSession()
    {
        try {
            $sessionId = SessionManager::create();

            Response::json([
                'success' => true,
                'session_id' => $sessionId,
                'stream_url' => '/@/mcp/stream?session_id=' . $sessionId
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle client requests (POST endpoint)
     * POST /@/mcp/request
     */
    public function handleRequest()
    {
        try {
            $data = Request::input();
            $sessionId = $data['session_id'] ?? null;
            $request = $data['request'] ?? null;

            if (!$sessionId || !$request) {
                Response::json([
                    'success' => false,
                    'error' => 'Missing session_id or request'
                ], 400);
                return;
            }

            if (!SessionManager::isValid($sessionId)) {
                Response::json([
                    'success' => false,
                    'error' => 'Invalid session'
                ], 401);
                return;
            }

            // Add request to queue
            SessionManager::addRequest($sessionId, $request);

            Response::json([
                'success' => true,
                'queued' => true
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle SSE stream
     * GET /@/mcp/stream?session_id=xxx
     */
    public function handleStream()
    {
        $sessionId = $_GET['session_id'] ?? null;

        if (!$sessionId) {
            $this->sendSseError('Missing session_id parameter');
            return;
        }

        if (!SessionManager::isValid($sessionId)) {
            $this->sendSseError('Invalid session');
            return;
        }

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable Nginx buffering

        // Disable PHP output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Disable PHP limits for long-running process
        set_time_limit(0);
        ignore_user_abort(false); // Stop if client disconnects

        // Send initial connection event
        $this->sendSseEvent('connected', [
            'session_id' => $sessionId,
            'server' => 'GaiaAlpha MCP SSE',
            'version' => '1.0.0'
        ]);

        $server = new Server();
        $startTime = time();
        $lastHeartbeat = time();
        $eventId = 0;

        // Main SSE loop
        while (time() - $startTime < $this->maxDuration) {
            // Check if connection is still alive
            if (connection_aborted()) {
                break;
            }

            // Check for pending requests
            $request = SessionManager::getNextRequest($sessionId);

            if ($request !== null) {
                try {
                    // Process request through MCP server
                    $response = $server->handleRequestPublic($request);

                    if ($response) {
                        $this->sendSseEvent('message', $response, ++$eventId);
                    }
                } catch (\Exception $e) {
                    $this->sendSseEvent('error', [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ], ++$eventId);
                }

                $lastHeartbeat = time();
            } else {
                // Send heartbeat if needed
                if (time() - $lastHeartbeat >= $this->heartbeatInterval) {
                    $this->sendSseEvent('ping', [
                        'timestamp' => time()
                    ]);
                    $lastHeartbeat = time();
                }

                // Small sleep to prevent CPU spinning
                usleep(100000); // 100ms
            }
        }

        // Connection timeout or completed
        $this->sendSseEvent('close', [
            'reason' => 'timeout',
            'duration' => time() - $startTime
        ]);

        // Clean up session
        SessionManager::destroy($sessionId);
    }

    /**
     * Send an SSE event
     * @param string $event Event name
     * @param mixed $data Event data
     * @param int|null $id Event ID
     */
    private function sendSseEvent($event, $data, $id = null)
    {
        if ($id !== null) {
            echo "id: $id\n";
        }
        echo "event: $event\n";
        echo "data: " . json_encode($data) . "\n\n";

        // Flush output immediately
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Send an SSE error and close connection
     * @param string $message Error message
     */
    private function sendSseError($message)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        echo "event: error\n";
        echo "data: " . json_encode(['error' => $message]) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
