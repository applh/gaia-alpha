# Performance Optimization Guide

Gaia Alpha is designed to be lightweight, but scaling any application requires attention to configuration and architecture. This guide covers key strategies for optimizing your production deployments.

## 1. Context-Based Loading
The most effective way to improve performance is to run less code. Gaia Alpha's **Context System** allows you to load only the plugins necessary for a specific request.

- **Admin Plugins**: Heavy plugins (e.g., `ApiBuilder`, `FormBuilder`) should be restricted to the `admin` context.
- **API Plugins**: Plugins serving JSON data should not load view engines or asset processors.

**Example `plugin.json`**:
```json
{
    "name": "HeavyAdminTool",
    "context": "admin"
}
```

## 2. OpCache Configuration
For production, enabling PHP's OpCache is mandatory. It stores precompiled script bytecode in shared memory, removing the need for PHP to load and parse scripts on each request.

**Recommended `php.ini` settings**:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0 ; CRITICAL for production performance
```

> **Note**: When `validate_timestamps` is 0, you must manually clear OpCache (`php_opcache_reset()`) or restart the PHP service after deploying code changes.

## 3. Database Optimization (SQLite)
Gaia Alpha uses SQLite by default. While fast, it benefits significantly from proper indexing and maintenance.

### Indexing
Ensure that columns used in `WHERE`, `ORDER BY`, and `JOIN` clauses are indexed.
```sql
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_todos_user_id ON todos(user_id);
```

### WAL Mode
Enable Write-Ahead Logging (WAL) for better concurrency (readers don't block writers).
```php
// In a tailored startup script or hook
DB::execute('PRAGMA journal_mode = WAL;');
```

## 4. Asset Caching
As detailed in the [Nginx Asset Caching](./performance_guide.md#nginx-asset-caching) section, ofloading asset delivery to Nginx prevents PHP from bootstrapping for CSS/JS requests.

## 5. API Best Practices
- **Partial Payloads**: Use the `fields` parameter (if supported) to request only necessary data.
- **Batching**: If possible, batch multiple operations into a single request to reduce HTTP overhead.
- **Caching**: Implement `Cache-Control` headers for public GET endpoints.
