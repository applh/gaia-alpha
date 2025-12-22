# Common Pitfalls and Solutions

This document covers common errors and their solutions when developing for Gaia Alpha CMS.

## Database Access

### ❌ WRONG: Using `DataStore::getDb()`
```php
use GaiaAlpha\DataStore;

$db = DataStore::getDb();
$stmt = $db->prepare('SELECT * FROM table');
$stmt->execute();
$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
```

**Error:** `Class "GaiaAlpha\DataStore" not found` or `Call to undefined method getDb()`

### ✅ CORRECT: Using `DB` class
```php
use GaiaAlpha\Model\DB;

// Fetch all rows
$results = DB::fetchAll('SELECT * FROM table WHERE user_id = ?', [$userId]);

// Fetch single row
$row = DB::fetch('SELECT * FROM table WHERE id = ?', [$id]);

// Execute INSERT/UPDATE/DELETE
DB::execute('INSERT INTO table (col1, col2) VALUES (?, ?)', [$val1, $val2]);

// Get last inserted ID
$id = DB::lastInsertId();
```

**Why:** The `DataStore` class is for key-value storage, not database queries. Use the `DB` class for all database operations.

---

## Authentication in Controllers

### ❌ WRONG: Not checking return value
```php
public function myEndpoint()
{
    $this->requireAuth();  // Sends JSON but continues execution!
    
    $userId = Session::id();  // Will be null if not authenticated
    $data = MyService::getData($userId);  // Fatal error!
    Response::json($data);
}
```

**Error:** `Argument #1 ($userId) must be of type int, null given`

### ✅ CORRECT: Check return value and exit
```php
public function myEndpoint()
{
    if (!$this->requireAuth()) return;  // Exit if not authenticated
    
    $userId = Session::id();
    $data = MyService::getData($userId);
    Response::json($data);
}
```

**Why:** `requireAuth()` sends a JSON error response but returns `false` without stopping execution. You must check the return value and exit early.

---

## Asset Serving

### ❌ WRONG: Incorrect plugin component path
```php
// In plugin index.php
UiManager::registerComponent('my_component', 'plugins/MyPlugin/resources/js/MyComponent.js', true);
```

**Error:** `404 Not Found` when loading component

### ✅ CORRECT: Component at plugin root
```php
// In plugin index.php
UiManager::registerComponent('my_component', 'plugins/MyPlugin/MyComponent.js', true);

// File location: plugins/MyPlugin/MyComponent.js (not in resources/js/)
```

**Why:** The `AssetController` checks the plugin root directory first, then `resources/js/`. For main components, place them at the plugin root to match other plugins' patterns.

---

### ❌ WRONG: Using ESM build with external CDN dependencies
```javascript
import { Chart } from 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/+esm';
```

**Error:** `Failed to resolve module specifier` or dependency on external network.

### ✅ CORRECT: Use local assets
```javascript
import { Chart, ... } from '/min/js/vendor/chart.js';
// Or use the Import Map
import { Chart } from 'chartjs'; 
```

**Why:** Production applications should not rely on external CDNs. They introduce security risks (SRI), privacy concerns, and can fail if the user's network blocks the CDN. Always mirror dependencies locally in `resources/js/vendor/`.

---

## User Interaction

### ❌ WRONG: Using Browser `alert()` or `confirm()`
```javascript
if (error) {
    alert("Something went wrong!");
}
```

**Error:** Blocks the main thread, looks unprofessional, and prevents a smooth SPA experience.

### ✅ CORRECT: Using the Toast system
```javascript
import { store } from '/min/js/store.js';

// For errors
store.addNotification("Successfully saved!", "success");

// For errors
store.addNotification("Failed to delete item", "error");
```

**Why:** The `ToastContainer` provides a non-blocking, themed notification system that integrates with the application's design language.

---

## API Security

### ❌ WRONG: Trusting raw input
```php
public function update($id) {
    $data = Request::input();
    DB::execute("UPDATE table SET name = '{$data['name']}' WHERE id = $id");
}
```

**Error:** SQL Injection, Type errors, and XSS.

### ✅ CORRECT: Validate, Type, and Parametrize
```php
public function update($id) {
    if (!$this->requireAuth()) return;
    
    $name = Request::input('name');
    if (!$name || !is_string($name)) {
        Response::json(['error' => 'Invalid name'], 400);
        return;
    }

    // Always use parameter binding
    DB::execute("UPDATE table SET name = ? WHERE id = ?", [
        htmlspecialchars($name), // Sanitize if it will be rendered as HTML later
        (int)$id
    ]);
    
    Response::json(['success' => true]);
}
```

**Why:** Never trust client-side data. Use typed helpers like `Request::queryInt()`, always use `?` placeholders in SQL, and validate that required fields exist before processing.

---

## Database Table Creation

### ❌ WRONG: Using PDO directly in hooks
```php
Hook::add('framework_init', function () {
    $db = DataStore::getDb();  // Wrong class!
    $stmt = $db->query("SELECT name FROM sqlite_master...");
    if (!$stmt->fetch()) {
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $db->exec($schema);  // May fail with multiple statements
    }
});
```

**Error:** Class not found, or schema not fully executed

### ✅ CORRECT: Create tables via CLI or use DB class
```bash
# Recommended: Use CLI to create tables
php cli.php sql "$(cat plugins/MyPlugin/schema.sql)"
```

Or if you must use a hook:
```php
Hook::add('framework_init', function () {
    // Check if table exists
    $exists = DB::fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='my_table'");
    
    if (!$exists) {
        // Execute each CREATE TABLE separately
        DB::execute("CREATE TABLE IF NOT EXISTS my_table (...)");
        DB::execute("CREATE INDEX IF NOT EXISTS idx_name ON my_table(...)");
    }
});
```

**Why:** Multi-statement SQL execution can be unreliable. Use CLI for initial setup or execute statements individually.

---

## Import Paths in Vue Components

### ❌ WRONG: Relative imports from moved files
```javascript
// In plugins/MyPlugin/MyComponent.js
import SubComponent from './components/SubComponent.js';  // Wrong after moving file!
```

**Error:** `404 Not Found`

### ✅ CORRECT: Absolute paths
```javascript
// In plugins/MyPlugin/MyComponent.js
import SubComponent from '/min/js/plugins/MyPlugin/components/SubComponent.js';
```

**Why:** When you move a component file, relative paths break. Use absolute paths through the `/min/js/` route for reliability.

---

## Response Handling

### ❌ WRONG: Not exiting after error response
```php
public function myEndpoint()
{
    if ($error) {
        Response::json(['error' => 'Something wrong'], 400);
        // Execution continues!
    }
    
    Response::json(['success' => true]);  // This also runs!
}
```

**Error:** Multiple responses sent, or execution continues after error

### ✅ CORRECT: Return after response
```php
public function myEndpoint()
{
    if ($error) {
        Response::json(['error' => 'Something wrong'], 400);
        return;  // Exit early
    }
    
    Response::json(['success' => true]);
}
```

**Why:** `Response::json()` doesn't exit automatically. Always `return` after sending a response.

---

## Session Access

### ❌ WRONG: Assuming session exists
```php
$userId = Session::id();  // May be null!
MyService::getData($userId);  // Type error if null!
```

**Error:** `Argument must be of type int, null given`

### ✅ CORRECT: Check authentication first
```php
if (!Session::isLoggedIn()) {
    Response::json(['error' => 'Unauthorized'], 401);
    return;
}

$userId = Session::id();  // Now guaranteed to be int
MyService::getData($userId);
```

**Why:** `Session::id()` returns `null` if not logged in. Always check authentication before accessing session data.

---

## JSON Parsing

### ❌ WRONG: Not handling JSON errors
```php
$config = json_decode($jsonString, true);
// Use $config without checking
```

**Error:** `null` used as array, causing fatal errors

### ✅ CORRECT: Validate JSON
```php
$config = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new \Exception('Invalid JSON: ' . json_last_error_msg());
}

// Now safe to use $config
```

**Why:** `json_decode()` returns `null` on error. Always check `json_last_error()` after decoding.

---

## Quick Reference

### Database Operations
```php
use GaiaAlpha\Model\DB;

// SELECT
$rows = DB::fetchAll($sql, $params);
$row = DB::fetch($sql, $params);

// INSERT/UPDATE/DELETE
DB::execute($sql, $params);
$id = DB::lastInsertId();
```

### Authentication
```php
if (!$this->requireAuth()) return;
$userId = Session::id();
```

### Component Registration
```php
// File: plugins/MyPlugin/MyComponent.js
UiManager::registerComponent('my_component', 'plugins/MyPlugin/MyComponent.js', true);
```

### Chart.js Import
```javascript
const ChartModule = await import('https://cdn.jsdelivr.net/npm/chart.js@4.4.7/+esm');
const { Chart, ...controllers } = ChartModule;
Chart.register(...controllers);
```

---

## Debugging Tips

1. **Check PHP errors in terminal** where `php -S` is running
2. **Check browser console** for JavaScript errors
3. **Test API endpoints with curl** before building UI
4. **Verify file paths** with `ls -la` before importing
5. **Check database** with `php cli.php sql "SELECT ..."`

---

## Common Error Messages

| Error | Likely Cause | Solution |
|-------|-------------|----------|
| `Class "GaiaAlpha\DataStore" not found` | Wrong import | Use `GaiaAlpha\Model\DB` |
| `Argument #1 must be of type int, null given` | Not checking auth | Add `if (!$this->requireAuth()) return;` |
| `404 Not Found` for component | Wrong path | Check file exists, use correct path |
| `Failed to resolve module specifier` | Missing dependency | Use CDN or bundle dependencies |
| `"line" is not a registered controller` | Not registering Chart.js | Call `Chart.register(...)` |
| `Unexpected token '<'` in JSON | PHP error returned as HTML | Check PHP errors, fix backend |
