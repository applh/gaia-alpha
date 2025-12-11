# Performance & Benchmarking

Gaia Alpha includes built-in tools to measure and monitor the performance of your application. This includes a suite of benchmarks for the CLI and hooks for profiling request lifecycles.

## Benchmarking Tools

You can run performance benchmarks directly from the command line.

### Running Benchmarks

**Run all benchmarks:**
```bash
php cli.php bench:all
```

**Run specific benchmarks:**
```bash
php cli.php bench:boot      # Measure framework initialization time
php cli.php bench:router    # Measure route matching performance
php cli.php bench:db        # Measure database query speed
php cli.php bench:template  # Measure template rendering speed
```

### Baseline Performance

Below are typical results on a development machine (Apple M1 Pro, PHP 8.2):

| Metric | Description | Typical Value |
| :--- | :--- | :--- |
| **Boot Time** | Framework initialization overhead | ~50-60 ms |
| **Router** | Route matching throughput | ~8,000 req/sec |
| **Database** | Simple `SELECT 1` queries | ~500,000 queries/sec |
| **Template** | Basic variable substitution | ~80,000 renders/sec |

*Note: Real-world application performance will vary based on complexity, database size, and server hardware.*

## Profiling Hooks

For detailed analysis of your application's lifecycle, Gaia Alpha provides several hooks that trigger at key moments. You can attach listeners to these hooks to measure execution time.

### Available Hooks

- **`app_init`**: Triggered at the very start of the application (CLI or Web).
- **`router_dispatch_before`**: Triggered immediately before the router attempts to match a request.
- **`router_matched`**: Triggered when a matching route is found.
- **`router_dispatch_after`**: Triggered after the route handler has executed (and returned).
- **`response_json_before`**: Triggered before data is encoded to JSON.
- **`response_send_before`**: Triggered just before the response is sent to the client.
- **`app_terminate`**: Triggered when the script execution finishes (shutdown).

### Example Profiler Plugin

You can create a simple plugin to log timing data:

```php
<?php
// my-data/plugins/profiler/index.php

use GaiaAlpha\Hook;

$start = microtime(true);

Hook::add('app_init', function() use ($start) {
    // Log start time
});

Hook::add('app_terminate', function() use ($start) {
    $end = microtime(true);
    $duration = ($end - $start) * 1000;
    error_log("Request Duration: {$duration}ms");
});
```
