<?php

namespace GaiaAlpha\Cli;

class Input
{
    private static ?array $args = null;

    /**
     * Get argument by index (0-based, starting after the command name)
     */
    public static function get(int $index, $default = null)
    {
        self::init();
        return self::$args[$index] ?? $default;
    }

    /**
     * Get all arguments after the command name
     */
    public static function all(): array
    {
        self::init();
        return self::$args;
    }

    /**
     * Get number of arguments
     */
    public static function count(): int
    {
        self::init();
        return count(self::$args);
    }

    /**
     * Check if argument exists
     */
    public static function has(int $index): bool
    {
        self::init();
        return isset(self::$args[$index]);
    }

    /**
     * Set arguments manually (used by Cli router)
     */
    public static function initFromArgv(array $args): void
    {
        self::$args = self::filterFlags($args);
    }

    /**
     * Filter out global framework flags
     */
    private static function filterFlags(array $args): array
    {
        return array_values(array_filter($args, function ($arg) {
            return !str_starts_with($arg, '--site=');
        }));
    }

    /**
     * Initialize arguments from $argv (default fallback)
     */
    private static function init(): void
    {
        if (self::$args !== null) {
            return;
        }

        global $argv;
        $args = $argv ?? [];
        $commandIndex = -1;

        // Find the first argument that is NOT a flag (the command)
        for ($i = 1; $i < count($args); $i++) {
            if (!str_starts_with($args[$i], '--')) {
                $commandIndex = $i;
                break;
            }
        }

        // If no command found, it's just flags or empty
        if ($commandIndex === -1) {
            self::$args = [];
        } else {
            // Everything after the command is an argument, but filter global flags
            self::$args = self::filterFlags(array_slice($args, $commandIndex + 1));
        }
    }
}
