<?php

namespace GaiaAlpha\Cli;

class Output
{
    private static array $colors = [
        'reset' => "\033[0m",
        'black' => "\033[0;30m",
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[0;33m",
        'blue' => "\033[0;34m",
        'magenta' => "\033[0;35m",
        'cyan' => "\033[0;36m",
        'white' => "\033[0;37m",
        'bold' => "\033[1m",
    ];

    /**
     * Write text to output
     */
    public static function write(string $text, ?string $color = null): void
    {
        if ($color && isset(self::$colors[$color])) {
            echo self::$colors[$color] . $text . self::$colors['reset'];
        } else {
            echo $text;
        }
    }

    /**
     * Write text followed by newline
     */
    public static function writeln(string $text = '', ?string $color = null): void
    {
        self::write($text . "\n", $color);
    }

    /**
     * Write success message
     */
    public static function success(string $message): void
    {
        self::writeln("SUCCESS: " . $message, 'green');
    }

    /**
     * Write error message
     */
    public static function error(string $message): void
    {
        self::writeln("ERROR: " . $message, 'red');
    }

    /**
     * Write warning message
     */
    public static function warning(string $message): void
    {
        self::writeln("WARNING: " . $message, 'yellow');
    }

    /**
     * Write info message
     */
    public static function info(string $message): void
    {
        self::writeln($message, 'cyan');
    }

    /**
     * Write section title
     */
    public static function title(string $title): void
    {
        self::writeln();
        self::writeln($title, 'bold');
        self::writeln(str_repeat('-', strlen($title)), 'bold');
    }

    /**
     * Render a table
     */
    public static function table(array $headers, array $rows): void
    {
        if (empty($headers)) {
            return;
        }

        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = strlen($header);
        }

        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $value) {
                $widths[$i] = max($widths[$i] ?? 0, strlen((string) $value));
            }
        }

        // Render headers
        foreach ($headers as $i => $header) {
            echo str_pad($header, $widths[$i] + 2);
        }
        echo "\n";

        // Render separator
        foreach ($widths as $width) {
            echo str_repeat('-', $width) . "  ";
        }
        echo "\n";

        // Render rows
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $value) {
                echo str_pad((string) $value, $widths[$i] + 2);
            }
            echo "\n";
        }
    }
}
