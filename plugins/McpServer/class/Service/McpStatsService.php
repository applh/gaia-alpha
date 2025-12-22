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
        // Calculate start date in PHP for portability
        $startDate = date('Y-m-d 00:00:00', strtotime("-$days days"));

        // Total Calls
        $totalCalls = DB::fetchColumn("SELECT COUNT(*) FROM cms_mcp_logs WHERE timestamp >= ?", [$startDate]);

        // Success Rate
        $successCount = DB::fetchColumn("SELECT COUNT(*) FROM cms_mcp_logs WHERE result_status = 'success' AND timestamp >= ?", [$startDate]);
        $successRate = $totalCalls > 0 ? round(($successCount / $totalCalls) * 100, 1) : 0;

        // Average Duration
        $avgDuration = DB::fetchColumn("SELECT AVG(duration) FROM cms_mcp_logs WHERE timestamp >= ?", [$startDate]);
        $avgDuration = round(($avgDuration ?? 0) * 1000, 2); // Convert to ms

        // Top Tools/Methods
        $topTools = DB::fetchAll("SELECT method, COUNT(*) as count 
                                FROM cms_mcp_logs 
                                WHERE timestamp >= ?
                                GROUP BY method 
                                ORDER BY count DESC 
                                LIMIT 5", [$startDate]);

        // History for graph
        $rawHistory = DB::fetchAll("
            SELECT SUBSTR(timestamp, 1, 10) as date, COUNT(*) as count 
            FROM cms_mcp_logs 
            WHERE timestamp >= ? 
            GROUP BY SUBSTR(timestamp, 1, 10)
            ORDER BY date ASC
        ", [$startDate]);

        $historyMap = [];
        foreach ($rawHistory as $row) {
            $historyMap[$row['date']] = (int) $row['count'];
        }

        $paddedHistory = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $paddedHistory[] = [
                'date' => $date,
                'count' => $historyMap[$date] ?? 0
            ];
        }

        return [
            'total_calls' => (int) $totalCalls,
            'success_rate' => $successRate,
            'avg_duration_ms' => $avgDuration,
            'top_tools' => $topTools,
            'history' => $paddedHistory
        ];
    }

    /**
     * Get recent logs
     */
    public static function getRecentLogs($limit = 50)
    {
        return DB::fetchAll("SELECT * FROM cms_mcp_logs ORDER BY timestamp DESC LIMIT ?", [$limit]);
    }
}
