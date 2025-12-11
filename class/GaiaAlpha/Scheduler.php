<?php

namespace GaiaAlpha;

class Scheduler
{
    private array $tasks = [];

    /**
     * Register a new task.
     *
     * @param callable $callback The code to execute.
     * @param string $frequency Cron expression (currently only supports simple checks or 'always')
     *                          For this MVP, we will stick to a simple 'every_minute' or custom logic in callback.
     *                          To keep it simple, we will execute all registered tasks and let them decide if they run.
     *                          Or we can implement basic frequency.
     */
    public function call(callable $callback): self
    {
        $this->tasks[] = $callback;
        return $this;
    }

    public function run(): void
    {
        $timestamp = time();
        echo "[Scheduler] Running tasks at " . date('Y-m-d H:i:s', $timestamp) . "\n";

        foreach ($this->tasks as $result) {
            // Ideally we check frequency here.
            // For MVP, we just run the callback.
            try {
                call_user_func($result);
            } catch (\Exception $e) {
                echo "[Scheduler] Error: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Singleton / Static launcher for easy usage
     */
    public static function simpleRun(): void
    {
        $scheduler = new self();

        // Register default system tasks here
        // Example: $scheduler->call(function() { ... });

        // Allow plugins/hooks to register tasks
        Hook::run('scheduler_register', $scheduler);

        $scheduler->run();
    }
}
