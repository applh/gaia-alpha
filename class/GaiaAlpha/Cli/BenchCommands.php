<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Database;
use GaiaAlpha\Router;
use GaiaAlpha\Env;
use GaiaAlpha\Cli\Output;
use PDO;

class BenchCommands
{
    public static function handleAll()
    {
        self::handleBoot();
        self::handleRouter();
        self::handleDb();
        self::handleTemplate();
    }

    public static function handleBoot()
    {
        Output::title("Benchmark: Boot Overhead");
        Output::info("Measuring CLI boot overhead...");

        $start = microtime(true);
        $iterations = 5; // Reduced from 10 to be faster

        for ($i = 0; $i < $iterations; $i++) {
            $cliPath = Env::get('root_dir') . '/cli.php';
            $cmd = 'php ' . escapeshellarg($cliPath) . ' help > /dev/null';
            exec($cmd);
        }

        $end = microtime(true);
        $total = $end - $start;
        $avg = ($total / $iterations) * 1000;

        Output::success("Boot Average: " . number_format($avg, 2) . "ms (over $iterations iterations)");
    }

    public static function handleRouter()
    {
        Output::title("Benchmark: Router Dispatch");
        Output::info("Benchmarking 1000 routes with 10000 matches...");

        // Setup many routes
        $routeCount = 1000;
        for ($i = 0; $i < $routeCount; $i++) {
            Router::get("/bench/route/$i", function () {});
        }

        // Match the last one
        $target = "/bench/route/" . ($routeCount - 1);

        $start = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            Router::dispatch('GET', $target);
        }

        $end = microtime(true);
        $total = $end - $start;
        $avg = ($total / $iterations) * 1000;
        $rps = $iterations / $total;

        Output::success("Router Check: " . number_format($avg, 4) . "ms per match");
        Output::writeln("Router RPS: " . number_format($rps, 2) . " req/sec", 'cyan');
    }

    public static function handleDb()
    {
        Output::title("Benchmark: Database Queries");
        Output::info("Executing 1000 SELECT 1 queries...");

        try {
            $start = microtime(true);
            $iterations = 1000;

            for ($i = 0; $i < $iterations; $i++) {
                \GaiaAlpha\Model\DB::query("SELECT 1");
            }

            $end = microtime(true);
            $total = $end - $start;
            $avg = ($total / $iterations) * 1000;
            $qps = $iterations / $total;

            Output::success("Database Query: " . number_format($avg, 4) . "ms per query");
            Output::writeln("Database QPS: " . number_format($qps, 2) . " queries/sec", 'cyan');

        } catch (\Exception $e) {
            Output::error("Database Benchmark Failed: " . $e->getMessage());
        }
    }

    public static function handleTemplate()
    {
        Output::title("Benchmark: Template Rendering");
        Output::info("Rendering simple PHP template 10000 times...");

        $templateFile = sys_get_temp_dir() . '/bench_template.php';
        \GaiaAlpha\File::write($templateFile, '<h1>Hello <?= $name ?></h1>');

        $name = "World";

        $start = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            ob_start();
            include $templateFile;
            ob_end_clean();
        }

        $end = microtime(true);
        $total = $end - $start;
        $avg = ($total / $iterations) * 1000;
        $ops = $iterations / $total;

        Output::success("Template Render: " . number_format($avg, 5) . "ms per render");
        Output::writeln("Template OPS: " . number_format($ops, 2) . " renders/sec", 'cyan');

        \GaiaAlpha\File::delete($templateFile);
    }
}
