<?php

namespace McpServer;

class SessionManager
{
    /**
     * Create a new MCP session
     * @return string Session ID
     */
    public static function create()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionId = bin2hex(random_bytes(16));
        $_SESSION['mcp'] = [
            'session_id' => $sessionId,
            'pending_requests' => [],
            'last_event_id' => 0,
            'created_at' => time(),
            'last_activity' => time()
        ];

        session_write_close();
        return $sessionId;
    }

    /**
     * Add a request to the session queue
     * @param string $sessionId
     * @param array $request JSON-RPC request
     * @throws \Exception if session is invalid
     */
    public static function addRequest($sessionId, $request)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['mcp']) || $_SESSION['mcp']['session_id'] !== $sessionId) {
            session_write_close();
            throw new \Exception('Invalid session');
        }

        $_SESSION['mcp']['pending_requests'][] = $request;
        $_SESSION['mcp']['last_activity'] = time();

        session_write_close();
    }

    /**
     * Get and remove the next pending request
     * @param string $sessionId
     * @return array|null Request or null if queue is empty
     */
    public static function getNextRequest($sessionId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['mcp']) || $_SESSION['mcp']['session_id'] !== $sessionId) {
            session_write_close();
            return null;
        }

        $request = null;
        if (!empty($_SESSION['mcp']['pending_requests'])) {
            $request = array_shift($_SESSION['mcp']['pending_requests']);
            $_SESSION['mcp']['last_activity'] = time();
        }

        session_write_close();
        return $request;
    }

    /**
     * Check if session is valid
     * @param string $sessionId
     * @return bool
     */
    public static function isValid($sessionId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $valid = isset($_SESSION['mcp']) && $_SESSION['mcp']['session_id'] === $sessionId;
        session_write_close();

        return $valid;
    }

    /**
     * Update last activity timestamp
     * @param string $sessionId
     */
    public static function touch($sessionId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['mcp']) && $_SESSION['mcp']['session_id'] === $sessionId) {
            $_SESSION['mcp']['last_activity'] = time();
        }

        session_write_close();
    }

    /**
     * Clean up old sessions
     * @param int $maxAge Maximum age in seconds (default: 1 hour)
     */
    public static function cleanup($maxAge = 3600)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            isset($_SESSION['mcp']) &&
            time() - $_SESSION['mcp']['created_at'] > $maxAge
        ) {
            unset($_SESSION['mcp']);
        }

        session_write_close();
    }

    /**
     * Destroy a session
     * @param string $sessionId
     */
    public static function destroy($sessionId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['mcp']) && $_SESSION['mcp']['session_id'] === $sessionId) {
            unset($_SESSION['mcp']);
        }

        session_write_close();
    }
}
