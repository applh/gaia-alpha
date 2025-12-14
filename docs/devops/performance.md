# Performance & Benchmarking

Gaia Alpha includes built-in tools to measure and monitor the performance of your application. This includes a suite of benchmarks for the CLI and hooks for profiling request lifecycles.

For a historical log of performance metrics, see [Performance History](performance_history.md).

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

## Comparative Performance

To help understand how Gaia Alpha fits into the ecosystem, here is a comparison of typical performance characteristics against common alternatives.

| Framework / CMS | Type | Typical Boot Time (CLI) | Request Overhead | Use Case |
| :--- | :--- | :--- | :--- | :--- |
| **Gaia Alpha** | **Hybrid Framework** | **~50-60 ms** | **Low** | **Apps & Tools** |
| Laravel / Symfony | Full-Stack Framework | ~80-150 ms | Moderate | Enterprise Apps |
| WordPress | CMS | ~200+ ms | High | Content Sites |
| Slim / Lumen | Micro-Framework | ~20-40 ms | Very Low | Microservices |

*Note: Gaia Alpha prioritizes a balance between developer experience (Vue.js, Auto-wiring) and raw performance. Values are approximate references for PHP 8.2.*

### When to use Gaia Alpha?

- **Use Gaia Alpha** when you need a fast, standalone application with a modern UI and zero infrastructure configuration.
- **Use Laravel/Symfony** when you need extensive ecosystem libraries, complex ORM features, or enterprise support.
- **Use WordPress** when your primary goal is content management and publishing.


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

## Nginx Asset Caching

When serving assets via the `AssetController` (e.g. `/min/css/...`), PHP is executed for every request to check cache validity. To achieve performance comparable to static files, you can configure Nginx to cache the output of these PHP requests.

### Configuration Example

Add the following to your Nginx server block:

```nginx
# Define cache path (in http block)
fastcgi_cache_path /var/cache/nginx/gaia_assets levels=1:2 keys_zone=gaia_assets:10m inactive=60m;

server {
    ...

    # Cache Minified Assets
    location ~ ^/min/(css|js)/ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Adjust for your PHP socket
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        
        # Enable Caching
        fastcgi_cache gaia_assets;
        fastcgi_cache_valid 200 60m; # Cache successful responses for 60 minutes
        fastcgi_cache_use_stale error timeout updating http_500;
        fastcgi_cache_lock on;
        
        # Bypass cache for development
        # fastcgi_cache_bypass $cookie_nocache;
        
        # Add header to debug cache status
        add_header X-FastCGI-Cache $upstream_cache_status;
    }
    
    ...
}
```

### Benefits
- **Zero PHP Overhead**: Subsequent requests are served directly by Nginx from disk/memory.
- **Massive Concurrency**: Nginx can handle thousands of static file requests per second with minimal CPU usage compared to PHP.
