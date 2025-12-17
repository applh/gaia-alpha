<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Database;
use GaiaAlpha\Router;
use GaiaAlpha\Env;
use PDO;

class BenchCommands
{
    public static function handleAll()
    {
        self::handleBoot();
        echo "\n";
        self::handleRouter();
        echo "\n";
        self::handleDb();
        echo "\n";
        self::handleTemplate();
    }

    public static function handleBoot()
    {
        echo "Running Boot Benchmark...\n";
        $start = microtime(true);
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            // Benchmark the CLI boot overhead by running a no-op command
            $cliPath = Env::get('root_dir') . '/cli.php';
            $cmd = 'php ' . escapeshellarg($cliPath) . ' help > /dev/null';
            exec($cmd);
        }

        $end = microtime(true);
        $total = $end - $start;
        $avg = ($total / $iterations) * 1000;

        echo "Boot Average: " . number_format($avg, 2) . "ms (over $iterations iterations)\n";
    }

    public static function handleRouter()
    {
        echo "Running Router Benchmark...\n";

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

        echo "Router Check: " . number_format($avg, 4) . "ms per match\n";
        echo "Router RPS: " . number_format($rps, 2) . " req/sec\n";
    }

    public static function handleDb()
    {
        echo "Running Database Benchmark...\n";

        try {
            // Benchmark the application's DB layer (BaseModel)
            // This includes connection (if not already connected) and logging overhead
            $start = microtime(true);
            $iterations = 1000;

            for ($i = 0; $i < $iterations; $i++) {
                \GaiaAlpha\Model\DB::query("SELECT 1");
            }

            $end = microtime(true);
            $total = $end - $start;
            $avg = ($total / $iterations) * 1000;
            $qps = $iterations / $total;

            echo "Database Query (BaseModel): " . number_format($avg, 4) . "ms per query\n";
            echo "Database QPS (BaseModel): " . number_format($qps, 2) . " queries/sec\n";

        } catch (\Exception $e) {
            echo "Database Benchmark Failed: " . $e->getMessage() . "\n";
        }
    }

    public static function handleTemplate()
    {
        echo "Running Template Benchmark...\n";

        $templateFile = sys_get_temp_dir() . '/bench_template.php';
        \GaiaAlpha\Filesystem::write($templateFile, '<h1>Hello <?= $name ?></h1>');

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

        echo "Template Render: " . number_format($avg, 5) . "ms per render\n";
        echo "Template OPS: " . number_format($ops, 2) . " renders/sec\n";

        \GaiaAlpha\Filesystem::delete($templateFile);
    }
}
