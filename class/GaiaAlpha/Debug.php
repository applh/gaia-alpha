<?php

namespace GaiaAlpha;

class Debug
{
    private static $queries = [];
    private static $route = null;
    private static $tasks = [];
    private static $timers = [];
    private static $memoryStart = 0;
    private static $startTime = 0;

    public static function init()
    {
        self::$startTime = microtime(true);
        self::$memoryStart = memory_get_usage();

        // Register hooks to capture data
        Hook::add('router_matched', [self::class, 'captureRoute']);
        Hook::add('app_task_before', [self::class, 'startTask']);
        Hook::add('app_task_after', [self::class, 'endTask']);
        Hook::add('response_send_before', [self::class, 'injectHeader']);
    }

    public static function injectHeader($data, $status)
    {
        // Only inject for Admins
        if (session_status() == PHP_SESSION_NONE) {
            session_start(); // Ensure session is open to check level
        }

        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            return;
        }

        $debugData = self::getData();

        // Strip traces to reduce header size
        foreach ($debugData['queries'] as &$query) {
            unset($query['trace']);
        }

        $json = json_encode($debugData);
        // Ensure valid header value (no newlines)
        $json = str_replace(["\r", "\n"], '', $json);

        header('X-Gaia-Debug: ' . $json);
    }

    public static function startTask($step, $task)
    {
        self::startTimer('task_' . $step);
    }

    public static function endTask($step, $task)
    {
        $duration = self::endTimer('task_' . $step);

        $taskName = is_string($task) ? $task : (is_array($task) ? (is_object($task[0]) ? get_class($task[0]) : $task[0]) . '::' . $task[1] : 'Closure');

        self::$tasks[] = [
            'step' => $step,
            'task' => $taskName,
            'duration' => $duration
        ];
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
            'tasks' => self::$tasks,
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
            'get' => $_GET,
            'user' => [
                'username' => $_SESSION['username'] ?? 'Guest',
                'level' => $_SESSION['level'] ?? 0
            ]
        ];
    }
}
