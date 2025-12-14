<?php

namespace GaiaAlpha;

class Debug
{
    private static $queries = [];
    private static $route = null;
    private static $timers = [];
    private static $memoryStart = 0;
    private static $startTime = 0;

    public static function init()
    {
        self::$startTime = microtime(true);
        self::$memoryStart = memory_get_usage();

        // Register hooks to capture data
        Hook::add('router_matched', [self::class, 'captureRoute']);
    }

    public static function captureRoute($route, $params)
    {
        self::$route = [
            'route' => $route,
            'params' => $params
        ];
    }

    public static function logQuery(string $sql, array $params = [], float $duration = 0)
    {
        self::$queries[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration, // in seconds
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) // Optional: capture trace
        ];
    }

    public static function startTimer(string $key)
    {
        self::$timers[$key] = microtime(true);
    }

    public static function endTimer(string $key)
    {
        if (isset(self::$timers[$key])) {
            $duration = microtime(true) - self::$timers[$key];
            unset(self::$timers[$key]);
            return $duration;
        }
        return 0;
    }

    public static function getData()
    {
        return [
            'queries' => self::$queries,
            'route' => self::$route,
            'memory' => [
                'current' => memory_get_usage(),
                'peak' => memory_get_peak_usage(),
                'start' => self::$memoryStart
            ],
            'time' => [
                'total' => microtime(true) - self::$startTime,
                'start' => self::$startTime
            ],
            'php_version' => PHP_VERSION,
            'post' => $_POST,
            'get' => $_GET
        ];
    }
}
