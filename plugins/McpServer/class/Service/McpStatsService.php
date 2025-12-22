<?php

namespace McpServer\Service;

use GaiaAlpha\Model\DB;
use PDO;

class McpStatsService
{
    /**
     * Get summary statistics for MCP activity
     */
    public static function getSummary($days = 30)
    {
        $db = DB::connect();
        $pdo = $db->getPdo();

        // Total Calls
        $totalCalls = $pdo->query("SELECT COUNT(*) FROM cms_mcp_logs WHERE timestamp > datetime('now', '-$days days')")->fetchColumn();

        // Success Rate
        $successCount = $pdo->query("SELECT COUNT(*) FROM cms_mcp_logs WHERE result_status = 'success' AND timestamp > datetime('now', '-$days days')")->fetchColumn();
        $successRate = $totalCalls > 0 ? round(($successCount / $totalCalls) * 100, 1) : 0;

        // Average Duration
        $avgDuration = $pdo->query("SELECT AVG(duration) FROM cms_mcp_logs WHERE timestamp > datetime('now', '-$days days')")->fetchColumn();
        $avgDuration = round($avgDuration * 1000, 2); // Convert to ms

        // Top Tools/Methods
        $topTools = $pdo->query("SELECT method, COUNT(*) as count 
                                FROM cms_mcp_logs 
                                WHERE timestamp > datetime('now', '-$days days')
                                GROUP BY method 
                                ORDER BY count DESC 
                                LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        // History for graph (last 30 days)
        $history = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $pdo->prepare("SELECT COUNT(*) FROM cms_mcp_logs WHERE date(timestamp) = ?");
            $count->execute([$date]);
            $history[] = [
                'date' => $date,
                'count' => $count->fetchColumn()
            ];
        }

        return [
            'total_calls' => (int) $totalCalls,
            'success_rate' => $successRate,
            'avg_duration_ms' => $avgDuration,
            'top_tools' => $topTools,
            'history' => $history
        ];
    }

    /**
     * Get recent logs
     */
    public static function getRecentLogs($limit = 50)
    {
        $db = DB::connect();
        $pdo = $db->getPdo();

        return $pdo->query("SELECT * FROM cms_mcp_logs ORDER BY timestamp DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    }
}
