<?php

namespace GaiaAlpha\Tests\Fiber;

use GaiaAlpha\Daemon\Loop;

class LoopTest
{
    public function testExecutionOrder()
    {
        $order = [];

        $loop = new Loop();

        $loop->async(function () use (&$order, $loop) {
            $order[] = 1;
            // Defer execution to next tick
            $loop->defer(function () use (&$order) {
                $order[] = 3;
            });
            $order[] = 2;
        });

        $loop->run();

        $expected = [1, 2, 3];

        if ($order === $expected) {
            echo "PASS: Loop Execution Order\n";
            return true;
        } else {
            echo "FAIL: Loop Order. Expected [1,2,3], got " . json_encode($order) . "\n";
            return false;
        }
    }
}

(new LoopTest())->testExecutionOrder();
