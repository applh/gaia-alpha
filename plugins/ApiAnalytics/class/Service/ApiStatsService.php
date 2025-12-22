<?php

namespace ApiAnalytics\Service;

use GaiaAlpha\Model\DB;

class ApiStatsService
{
    public static function getSummary($days = 30)
    {
        $startDate = date('Y-m-d 00:00:00', strtotime("-$days days"));

        // Traffic Summary
        $totalRequests = DB::fetchColumn("SELECT COUNT(*) FROM cms_api_logs WHERE timestamp >= ?", [$startDate]);

        $successCount = DB::fetchColumn("SELECT COUNT(*) FROM cms_api_logs WHERE status_code >= 200 AND status_code < 400 AND timestamp >= ?", [$startDate]);
        $successRate = $totalRequests > 0 ? round(($successCount / $totalRequests) * 100, 1) : 0;

        // Latency
        $avgLatency = DB::fetchColumn("SELECT AVG(duration) FROM cms_api_logs WHERE timestamp >= ?", [$startDate]);
        $avgLatency = round(($avgLatency ?? 0) * 1000, 2);

        // Slowest Endpoints (P95 is hard in SQL without window functions support everywhere)
        // Let's do TOP 5 slowest by average
        $slowest = DB::fetchAll("
            SELECT route_pattern, AVG(duration) as avg_duration, COUNT(*) as count 
            FROM cms_api_logs 
            WHERE timestamp >= ? AND route_pattern IS NOT NULL
            GROUP BY route_pattern 
            ORDER BY avg_duration DESC 
            LIMIT 5
        ", [$startDate]);

        // Status Distribution
        $statusDist = DB::fetchAll("
            SELECT 
                CASE 
                    WHEN status_code < 300 THEN '2xx'
                    WHEN status_code < 400 THEN '3xx'
                    WHEN status_code < 500 THEN '4xx'
                    ELSE '5xx'
                END as category,
                COUNT(*) as count
            FROM cms_api_logs
            WHERE timestamp >= ?
            GROUP BY category
        ", [$startDate]);

        // History
        $rawHistory = DB::fetchAll("
            SELECT SUBSTR(timestamp, 1, 10) as date, COUNT(*) as count 
            FROM cms_api_logs 
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
            'total_requests' => (int) $totalRequests,
            'success_rate' => $successRate,
            'avg_latency_ms' => $avgLatency,
            'slowest_endpoints' => $slowest,
            'status_distribution' => $statusDist,
            'history' => $paddedHistory
        ];
    }

    public static function getRecentLogs($limit = 100)
    {
        return DB::fetchAll("SELECT * FROM cms_api_logs ORDER BY timestamp DESC LIMIT ?", [$limit]);
    }
}
