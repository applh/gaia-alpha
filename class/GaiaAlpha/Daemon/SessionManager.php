<?php

namespace GaiaAlpha\Daemon;

class SessionManager
{
    private static ?SessionManager $instance = null;
    private array $sessions = [];

    public static function get(): SessionManager
    {
        if (self::$instance === null) {
            self::$instance = new SessionManager();
        }
        return self::$instance;
    }

    public function createSession(Stream $stream): string
    {
        // Simple generic ID
        $id = bin2hex(random_bytes(16));
        $this->sessions[$id] = $stream;
        return $id;
    }

    public function getSession(string $id): ?Stream
    {
        return $this->sessions[$id] ?? null;
    }

    public function closeSession(string $id): void
    {
        if (isset($this->sessions[$id])) {
            unset($this->sessions[$id]);
        }
    }
}
