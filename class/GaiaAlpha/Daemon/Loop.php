<?php

namespace GaiaAlpha\Daemon;

use Fiber;

class Loop
{
    private static ?Loop $instance = null;
    private Scheduler $scheduler;

    private array $readStreams = [];
    private array $writeStreams = [];
    private array $readCallbacks = [];
    private array $writeCallbacks = [];

    // Future tick callbacks
    private array $futureTickCallbacks = [];

    public function __construct()
    {
        $this->scheduler = new Scheduler();
    }

    public static function get(): Loop
    {
        if (self::$instance === null) {
            self::$instance = new Loop();
        }
        return self::$instance;
    }

    public function addReadStream($stream, callable $callback): void
    {
        $id = (int) $stream;
        $this->readStreams[$id] = $stream;
        $this->readCallbacks[$id] = $callback;
    }

    public function removeReadStream($stream): void
    {
        $id = (int) $stream;
        unset($this->readStreams[$id], $this->readCallbacks[$id]);
    }

    public function addWriteStream($stream, callable $callback): void
    {
        $id = (int) $stream;
        $this->writeStreams[$id] = $stream;
        $this->writeCallbacks[$id] = $callback;
    }

    public function removeWriteStream($stream): void
    {
        $id = (int) $stream;
        unset($this->writeStreams[$id], $this->writeCallbacks[$id]);
    }

    /**
     * Defer execution to the next tick of the loop
     */
    public function defer(callable $callback): void
    {
        $this->futureTickCallbacks[] = $callback;
    }

    /**
     * Spawn a new "Process" (Fiber)
     */
    public function async(callable $callback): void
    {
        $this->scheduler->enqueue($callback);
    }

    /**
     * Pause the current fiber until the stream is readable
     */
    public static function awaitReadable($stream): void
    {
        $loop = self::get();
        $fiber = Fiber::getCurrent();

        if (!$fiber) {
            throw new \RuntimeException('awaitReadable must be called within a Fiber');
        }

        $loop->addReadStream($stream, function () use ($loop, $stream, $fiber) {
            $loop->removeReadStream($stream);
            $loop->scheduler->schedule($fiber);
        });

        Fiber::suspend();
    }

    /**
     * Pause the current fiber until the stream is writable
     */
    public static function awaitWritable($stream): void
    {
        $loop = self::get();
        $fiber = Fiber::getCurrent();

        if (!$fiber) {
            throw new \RuntimeException('awaitWritable must be called within a Fiber');
        }

        $loop->addWriteStream($stream, function () use ($loop, $stream, $fiber) {
            $loop->removeWriteStream($stream);
            $loop->scheduler->schedule($fiber);
        });

        Fiber::suspend();
    }

    /**
     * Sleep for a number of seconds (non-blocking)
     */
    public static function sleep(float $seconds): void
    {
        // For simplicity in Phase 1, we won't implement a precise timer heap yet.
        // We'll just rely on stream_select timeout for now, or immediate return if 0.
        // Implementation TODO: Add TimerHeap
        $start = microtime(true);
        $until = $start + $seconds;

        $fiber = Fiber::getCurrent();
        if (!$fiber)
            return;

        // Very naive "sleep" by deferring until time passes. 
        // In a real loop, we'd add to a timer queue.
        // For now, let's just defer once.
        Fiber::suspend();
    }

    public function run(): void
    {
        // Initial kick-off of queued fibers
        $this->scheduler->run();

        while (!empty($this->readStreams) || !empty($this->writeStreams) || !empty($this->futureTickCallbacks)) {

            // Process any deferred callbacks first
            if (!empty($this->futureTickCallbacks)) {
                $callbacks = $this->futureTickCallbacks;
                $this->futureTickCallbacks = [];
                foreach ($callbacks as $cb) {
                    $this->scheduler->enqueue($cb);
                }
                $this->scheduler->run();
                // Continue to select only if we still have streams, otherwise we might just be processing CPU tasks
                if (empty($this->readStreams) && empty($this->writeStreams)) {
                    continue;
                }
            }

            $read = $this->readStreams;
            $write = $this->writeStreams;
            $except = null;

            // Use a short timeout to allow checking futureTickCallbacks or simply yielding
            // timeout 200ms
            $num = @stream_select($read, $write, $except, 0, 200000);

            if ($num === false) {
                // Interrupted system call?
                break;
            }

            if ($num > 0) {
                foreach ($read as $stream) {
                    $id = (int) $stream;
                    if (isset($this->readCallbacks[$id])) {
                        ($this->readCallbacks[$id])($stream);
                    }
                }
                foreach ($write as $stream) {
                    $id = (int) $stream;
                    if (isset($this->writeCallbacks[$id])) {
                        ($this->writeCallbacks[$id])($stream);
                    }
                }

                // Run any fibers that were scheduled by the callbacks
                $this->scheduler->run();
            }
        }
    }
}
