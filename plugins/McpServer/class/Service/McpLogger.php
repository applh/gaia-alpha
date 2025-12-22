<?php

namespace McpServer\Service;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Env;

class McpLogger
{
    /**
     * Log an MCP request and its response
     * 
     * @param array $request The JSON-RPC request
     * @param array|null $response The JSON-RPC response
     * @param float $duration Execution duration in seconds
     * @param string|null $sessionId The MCP session ID
     * @param string $site The site domain
     * @param array $clientInfo Information about the client (name, version)
     */
    public static function logRequest($request, $response, $duration, $sessionId = null, $site = 'default', $clientInfo = [])
    {
        $db = DB::connect();
        $pdo = $db->getPdo();

        $method = $request['method'] ?? 'unknown';
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

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
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
    }
}
