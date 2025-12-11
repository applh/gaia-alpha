<?php

namespace GaiaAlpha;

class Hook
{
    private static array $hooks = [];

    /**
     * Add a listener to a hook
     * 
     * @param string $name Name of the hook
     * @param callable $callback Function to execute
     * @param int $priority Priority (lower numbers run first)
     */
    public static function add(string $name, callable $callback, int $priority = 10)
    {
        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = [];
        }

        self::$hooks[$name][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
    }

    /**
     * Trigger a hook
     * 
     * @param string $name Name of the hook
     * @param mixed ...$args Arguments to pass to listeners
     */
    public static function run(string $name, ...$args)
    {
        if (!isset(self::$hooks[$name])) {
            return;
        }

        // Sort by priority
        usort(self::$hooks[$name], function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach (self::$hooks[$name] as $hook) {
            call_user_func_array($hook['callback'], $args);
        }
    }

    /**
     * Filter a value through hooks
     *
     * @param string $name Name of the hook
     * @param mixed $value The value to filter
     * @param mixed ...$args Additional arguments
     * @return mixed The filtered value
     */
    public static function filter(string $name, $value, ...$args)
    {
        if (!isset(self::$hooks[$name])) {
            return $value;
        }

        // Sort by priority
        usort(self::$hooks[$name], function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach (self::$hooks[$name] as $hook) {
            // Pass value as first argument, then others
            $callArgs = array_merge([$value], $args);
            $newValue = call_user_func_array($hook['callback'], $callArgs);
            // Update value for next hook
            $value = $newValue;
        }

        return $value;
    }
}
