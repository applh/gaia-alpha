<?php

namespace GaiaAlpha;

class Session
{
    /**
     * Start the session safely if not already started.
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get a value from the session.
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a value in the session.
     */
    public static function set(string $key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Destroy the session.
     */
    public static function destroy()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
    }

    /**
     * Create a new session for a user (Login).
     */
    public static function login(array $user)
    {
        self::start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['level'] = (int) $user['level'];
    }

    /**
     * Clear the current user session (Logout).
     */
    public static function logout()
    {
        self::start();
        session_unset();
        session_destroy();
    }

    /**
     * Check if a user is currently logged in.
     */
    public static function isLoggedIn(): bool
    {
        self::start();
        return isset($_SESSION['user_id']);
    }

    /**
     * Check if the current user is an Admin (level >= 100).
     */
    public static function isAdmin(): bool
    {
        self::start();
        return isset($_SESSION['level']) && $_SESSION['level'] >= 100;
    }

    /**
     * Get the current User ID.
     */
    public static function id()
    {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get the current User Level.
     */
    public static function level(): int
    {
        self::start();
        return (int) ($_SESSION['level'] ?? 0);
    }
}
