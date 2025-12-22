<?php

namespace GaiaAlpha;

class Env
{
    private static array $vars = [];

    public static function set(string $key, $value): void
    {
        self::$vars[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return self::$vars[$key] ?? $default;
    }

    public static function add(string $key, $value, ?string $index = null): void
    {
        if (!isset(self::$vars[$key]) || !is_array(self::$vars[$key])) {
            self::$vars[$key] = [];
        }

        if ($index !== null) {
            self::$vars[$key][$index] = $value;
        } else {
            self::$vars[$key][] = $value;
        }
    }
}
