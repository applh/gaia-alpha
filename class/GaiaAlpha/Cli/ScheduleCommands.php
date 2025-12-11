<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Scheduler;

class ScheduleCommands
{
    public static function handleRun(): void
    {
        Scheduler::simpleRun();
    }
}
