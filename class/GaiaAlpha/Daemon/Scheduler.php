<?php

namespace GaiaAlpha\Daemon;

use Fiber;
use Throwable;

class Scheduler
{
    private array $fibers = [];
    private array $queue = [];

    public function enqueue(callable $callback): void
    {
        $fiber = new Fiber($callback);
        $this->fibers[spl_object_id($fiber)] = $fiber;
        $this->queue[] = $fiber;
    }

    public function run(): void
    {
        while (!empty($this->queue)) {
            $fiber = array_shift($this->queue);

            try {
                if (!$fiber->isStarted()) {
                    $fiber->start();
                } elseif ($fiber->isSuspended()) {
                    $fiber->resume();
                }
            } catch (Throwable $e) {
                // In a real server, we should log this properly
                echo "Fiber Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
            }

            if ($fiber->isTerminated()) {
                unset($this->fibers[spl_object_id($fiber)]);
            }
        }
    }

    public function schedule(Fiber $fiber): void
    {
        $this->queue[] = $fiber;
    }
}
