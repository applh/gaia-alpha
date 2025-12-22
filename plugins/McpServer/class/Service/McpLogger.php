<?php

namespace McpServer\Service;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Env;

class McpLogger
{
    /**
     * Log an MCP request and its response (Triggered by hook)
     * 
     * @param array $payload Keyed array containing request, response, duration, etc.
     */
    public static function logRequest(array $payload)
    {
        $request = $payload['request'] ?? [];
        $response = $payload['response'] ?? null;
        $duration = $payload['duration'] ?? 0;
        $sessionId = $payload['sessionId'] ?? null;
        $site = $payload['siteDomain'] ?? 'default';
        $clientInfo = $payload['clientInfo'] ?? [];

        $method = $request['method'] ?? 'unknown';

        // Don't log internal pings
        if ($method === 'ping') {
            return;
        }

        $params = isset($request['params']) ? json_encode($request['params']) : null;

        $resultStatus = 'success';
        $errorMessage = null;

        if (isset($response['error'])) {
            $resultStatus = 'error';
            $errorMessage = $response['error']['message'] ?? 'Unknown error';
        }

        $sql = "INSERT INTO cms_mcp_logs 
                (session_id, method, params, result_status, error_message, duration, site_domain, client_name, client_version) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            DB::execute($sql, [
                $sessionId,
                $method,
                $params,
                $resultStatus,
                $errorMessage,
                $duration,
                $site,
                $clientInfo['name'] ?? null,
                $clientInfo['version'] ?? null
            ]);
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist yet or other DB error
        }
    }
}
