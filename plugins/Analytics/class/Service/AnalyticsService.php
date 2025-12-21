<?php

namespace Analytics\Service;

use GaiaAlpha\Model\DB;
use PDO;

class AnalyticsService
{


    public static function trackVisit($path, $userAgent, $ip, $referrer)
    {
        DB::query("INSERT INTO cms_analytics_visits (page_path, user_agent, visitor_ip, referrer) VALUES (?, ?, ?, ?)", [$path, $userAgent, $ip, $referrer]);
    }

    public static function getStats($days = 30)
    {
        // Total visits and Unique visitors
        $totalVisits = DB::fetchColumn("SELECT COUNT(*) FROM cms_analytics_visits");
        $uniqueVisitors = DB::fetchColumn("SELECT COUNT(DISTINCT visitor_ip) FROM cms_analytics_visits");

        // Visits today
        $todayStart = date('Y-m-d 00:00:00');
        $todayVisits = DB::fetchColumn("SELECT COUNT(*) FROM cms_analytics_visits WHERE timestamp >= ?", [$todayStart]);
        $todayUnique = DB::fetchColumn("SELECT COUNT(DISTINCT visitor_ip) FROM cms_analytics_visits WHERE timestamp >= ?", [$todayStart]);

        // Top Pages
        $topPages = DB::fetchAll("SELECT page_path, COUNT(*) as count FROM cms_analytics_visits GROUP BY page_path ORDER BY count DESC LIMIT 10");

        // Visits over time (last 30 days)
        $startDate = date('Y-m-d 00:00:00', strtotime("-$days days"));

        // Use a generic date formatting approach or handle aggregation in PHP if SQL dialect varies too much.
        // For basic "per day" grouping, standard SQL is tricky. 
        // SQLite: strftime('%Y-%m-%d', timestamp)
        // MySQL: DATE_FORMAT(timestamp, '%Y-%m-%d') or DATE(timestamp)
        // Postgres: to_char(timestamp, 'YYYY-MM-DD')

        // Since we can't easily unify GROUP BY date(timestamp) across all 3 without a helper, 
        // let's fetch raw data and aggregate in PHP for maximum compatibility, 
        // assuming the dataset for 30 days isn't massive (it's analytics, so it might be large, but let's assume reasonable for now).
        // Alternatively, use substrings for YYYY-MM-DD if timestamp format is guaranteed. 
        // We standardized to DATETIME (YYYY-MM-DD HH:MM:SS), so SUBSTR(timestamp, 1, 10) works on SQLite and MySQL/Postgres (as text cast).

        $rawHistory = DB::fetchAll("
            SELECT SUBSTR(timestamp, 1, 10) as date, COUNT(*) as count, COUNT(DISTINCT visitor_ip) as unique_count 
            FROM cms_analytics_visits 
            WHERE timestamp >= ? 
            GROUP BY SUBSTR(timestamp, 1, 10)
        ", [$startDate]);

        $historyMap = [];
        foreach ($rawHistory as $row) {
            $historyMap[$row['date']] = [
                'count' => (int) $row['count'],
                'unique' => (int) $row['unique_count']
            ];
        }

        $paddedHistory = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $paddedHistory[] = [
                'date' => $d,
                'count' => $historyMap[$d]['count'] ?? 0,
                'unique' => $historyMap[$d]['unique'] ?? 0
            ];
        }

        // Referrers
        $referrers = DB::fetchAll("SELECT referrer, COUNT(*) as count FROM cms_analytics_visits WHERE referrer IS NOT NULL AND referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT 5");

        // Browser, OS, and Device Stats
        $allUas = DB::fetchAll("SELECT user_agent FROM cms_analytics_visits");
        $browsers = [];
        $os = [];
        $devices = [];

        foreach ($allUas as $row) {
            $ua = $row['user_agent'];
            $parsed = self::parseUserAgent($ua);

            $b = $parsed['browser'];
            $browsers[$b] = ($browsers[$b] ?? 0) + 1;

            $o = $parsed['os'];
            $os[$o] = ($os[$o] ?? 0) + 1;

            $d = $parsed['device'];
            $devices[$d] = ($devices[$d] ?? 0) + 1;
        }

        // Convert to sorted lists
        $browserList = [];
        foreach ($browsers as $name => $count) {
            $browserList[] = ['name' => $name, 'count' => $count];
        }
        usort($browserList, fn($a, $b) => $b['count'] <=> $a['count']);

        $osList = [];
        foreach ($os as $name => $count) {
            $osList[] = ['name' => $name, 'count' => $count];
        }
        usort($osList, fn($a, $b) => $b['count'] <=> $a['count']);

        $deviceList = [];
        foreach ($devices as $name => $count) {
            $deviceList[] = ['name' => $name, 'count' => $count];
        }
        usort($deviceList, fn($a, $b) => $b['count'] <=> $a['count']);

        return [
            'total_visits' => (int) $totalVisits,
            'unique_visitors' => (int) $uniqueVisitors,
            'today_visits' => (int) $todayVisits,
            'today_unique' => (int) $todayUnique,
            'top_pages' => $topPages,
            'history' => $paddedHistory,
            'referrers' => $referrers,
            'browsers' => array_slice($browserList, 0, 5),
            'os' => array_slice($osList, 0, 5),
            'devices' => $deviceList
        ];
    }

    private static function parseUserAgent($ua)
    {
        $browser = 'Unknown';
        $os = 'Unknown';
        $device = 'Desktop';

        // Basic OS detection
        if (preg_match('/Windows/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
            $device = 'Mobile';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            $os = 'iOS';
            $device = 'Mobile';
            if (preg_match('/iPad/i', $ua))
                $device = 'Tablet';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        // Refine device detection
        if ($device === 'Desktop' && preg_match('/Mobile|phone|opera mini|blackberry|palm|hiptop/i', $ua)) {
            $device = 'Mobile';
        }
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            $device = 'Tablet';
        }

        // Basic Browser detection
        if (preg_match('/Edge/i', $ua))
            $browser = 'Edge';
        elseif (preg_match('/Chrome/i', $ua))
            $browser = 'Chrome';
        elseif (preg_match('/Safari/i', $ua))
            $browser = 'Safari';
        elseif (preg_match('/Firefox/i', $ua))
            $browser = 'Firefox';
        elseif (preg_match('/MSIE|Trident/i', $ua))
            $browser = 'IE';

        return ['browser' => $browser, 'os' => $os, 'device' => $device];
    }
}
