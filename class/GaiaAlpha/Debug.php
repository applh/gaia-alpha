<?php

namespace GaiaAlpha;

class Debug
{
    private static $queries = [];
    private static $route = null;
    private static $tasks = [];
    private static $timers = [];
    private static $pluginLogs = [];
    private static $memoryStart = 0;
    private static $startTime = 0;
    private static $currentTask = null;

    public static function init()
    {
        self::$startTime = microtime(true);
        self::$memoryStart = memory_get_usage();

        // Register hooks to capture data
        Hook::add('router_matched', [self::class, 'captureRoute']);
        Hook::add('app_task_before', [self::class, 'startTask']);
        Hook::add('app_task_after', [self::class, 'endTask']);
        Hook::add('response_send', [self::class, 'injectHeader']);
    }

    /**
     * Injects Debug Header or Replaces Body Placeholder
     * 
     * @param array|null $context Context array with content reference (e.g. ['content' => &$content])
     */
    public static function injectHeader($context = null)
    {
        // Only inject for Admins
        if (session_status() == PHP_SESSION_NONE) {
            session_start(); // Ensure session is open to check level
        }

        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            return;
        }

        $debugData = self::getData();

        // Strip traces to reduce header size for X-Gaia-Debug
        $headerData = $debugData;
        foreach ($headerData['queries'] as &$query) {
            unset($query['trace']);
        }

        $json = json_encode($headerData);
        // Ensure valid header value (no newlines)
        $json = str_replace(["\r", "\n"], '', $json);

        header('X-Gaia-Debug: ' . $json);

        // If content is passed (from Response::send hook), replace placeholder
        if (is_array($context) && isset($context['content'])) {
            $fullJson = json_encode($debugData);

            // Modify content reference inside array
            // Replace the string literal placeholder with the JSON object
            $search = '"__GAIA_DEBUG_DATA_PLACEHOLDER__"';
            if (strpos($context['content'], $search) !== false) {
                $context['content'] = str_replace($search, $fullJson, $context['content']);
            }
        }
    }

    public static function startTask($step, $task)
    {
        self::$currentTask = ['step' => $step, 'task' => $task];
        self::startTimer('task_' . $step);
    }

    public static function endTask($step, $task)
    {
        $duration = self::endTimer('task_' . $step);
        self::$currentTask = null;

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

    public static function logPlugin(string $plugin, string $message, array $context = [])
    {
        self::$pluginLogs[] = [
            'plugin' => $plugin,
            'message' => $message,
            'context' => $context,
            'time' => microtime(true) - self::$startTime
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
        $tasks = self::$tasks;

        // If there is a currently running task, append it as active
        if (self::$currentTask) {
            $step = self::$currentTask['step'];
            $task = self::$currentTask['task'];

            // Calculate elapsed time so far
            $duration = 0;
            if (isset(self::$timers['task_' . $step])) {
                $duration = microtime(true) - self::$timers['task_' . $step];
            }

            $taskName = is_string($task) ? $task : (is_array($task) ? (is_object($task[0]) ? get_class($task[0]) : $task[0]) . '::' . $task[1] : 'Closure');

            // If the active task is the flush task, don't mark it as active (visual polish)
            $suffix = ' (active)';
            if (strpos($taskName, 'Response::flush') !== false) {
                $suffix = '';
            }

            $tasks[] = [
                'step' => $step,
                'task' => $taskName . $suffix,
                'duration' => $duration
            ];
        }

        return [
            'queries' => self::$queries,
            'tasks' => $tasks,
            'plugin_logs' => self::$pluginLogs,
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
