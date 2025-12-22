<?php

namespace ApiAnalytics\Service;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Request;
use GaiaAlpha\Session;
use GaiaAlpha\SiteManager;

class ApiLogger
{
    private static $startTime = null;
    private static $capturedPattern = null;

    public static function startTimer()
    {
        self::$startTime = microtime(true);
    }

    public static function setPattern($pattern)
    {
        self::$capturedPattern = $pattern;
    }

    public static function log($statusCode = 200)
    {
        if (self::$startTime === null) {
            return;
        }

        $duration = microtime(true) - self::$startTime;
        $path = Request::path();
        $method = Request::server('REQUEST_METHOD', 'GET');

        // Only log API and internal paths (@)
        if (strpos($path, '/api/') !== 0 && strpos($path, '/@/') !== 0) {
            return;
        }

        $sql = "INSERT INTO cms_api_logs 
                (method, path, route_pattern, status_code, duration, user_id, site_domain, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            DB::execute($sql, [
                $method,
                $path,
                self::$capturedPattern,
                $statusCode,
                $duration,
                Session::id(),
                SiteManager::getCurrentSite() ?? 'default',
                Request::ip(),
                Request::userAgent()
            ]);
        } catch (\Exception $e) {
            // Table might not exist or other DB issue
        }
    }
}
