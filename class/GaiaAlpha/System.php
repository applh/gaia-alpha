<?php

namespace GaiaAlpha;

use GaiaAlpha\Hook;

class System
{
    /**
     * persistent boolean to track if the last check found the tool
     */
    private static array $tools = [];

    /**
     * Check if a command-line tool is available
     */
    public static function isAvailable(string $tool): bool
    {
        if (isset(self::$tools[$tool])) {
            return self::$tools[$tool];
        }

        $path = trim(shell_exec("which " . escapeshellarg($tool)));
        $exists = !empty($path);

        self::$tools[$tool] = $exists;

        return $exists;
    }

    /**
     * Execute a command
     *
     * @param string $command The command to execute
     * @param array|null $output Reference to store output lines
     * @param int|null $returnVar Reference to store return code
     * @return string|false The last line of output or false on failure
     */
    public static function exec(string $command, ?array &$output = null, ?int &$returnVar = null): string|false
    {
        // Trigger before hook
        // Allows plugins to modify the command or abort (by returning false)
        $command = Hook::filter('system_exec_before', $command);

        if ($command === false) {
            return false;
        }

        $result = exec($command, $output, $returnVar);

        // Trigger after hook
        Hook::run('system_exec_after', $command, $output, $returnVar);

        return $result;
    }

    /**
     * Escape a command for shell execution
     */
    public static function escapeCmd(string $cmd): string
    {
        return escapeshellcmd($cmd);
    }

    /**
     * Escape an argument for shell execution
     */
    public static function escapeArg(string $arg): string
    {
        return escapeshellarg($arg);
    }
}
