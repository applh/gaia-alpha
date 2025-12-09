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
}
