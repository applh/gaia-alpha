# System Architecture

## Overview
Gaia Alpha is a lightweight PHP framework designed for simplicity and security. It separates public assets from private application logic and data.

## Directory Structure
The application follows a strict separation between public-facing code and private logic.

```
gaia-alpha/
├── www/                # Public Web Root (Served by Web Server)
│   ├── index.php       # Frontend Entry Point
│
├── resources/          # Source Assets (Served via AssetController)
│   ├── js/             # Vue.js Application & Components
│   └── css/            # Stylesheets
│
├── class/              # Private PHP Classes (Autoloaded)
│   ├── autoload.php    # Autoloader Service
│   └── GaiaAlpha/      # Namespace: GaiaAlpha
│       ├── App.php     # Core Application Logic (Static)
│       ├── Framework.php # Controller & Route Logic
│       ├── Env.php     # Configuration & Registry
│       ├── Router.php  # Request Routing
│       ├── Database.php# Database Wrapper
│       ├── Media.php   # Image Processing API
│       ├── Cli.php     # CLI Runner Logic (Static)
│       ├── Cli/        # CLI Command Groups & IO
│       │   ├── Input.php # CLI Input Helper
│       │   ├── Output.php# CLI Output Helper
│       │   └── ...Commands.php # Specific command groups
│       ├── Controller/ # Request Controllers (Auth, Cms, etc.)
│       └── Model/      # Data Models (User, Page, etc.)
│           └── DB.php  # Database Helper (Static Methods)
│
├── templates/          # PHP Templates (HTML Views)
│
├── my-data/            # PRIVATE Data (GitIgnored)
│   ├── database.sqlite # SQLite Database
│   ├── api-config.json # API Builder Configuration
│   ├── uploads/        # Raw User Uploads
│   ├── logs/           # Application Logs
│   ├── cache/          # Processed Image Cache
│   │   ├── min/        # Minified Assets (Site specific)
│   │   └── templates/  # Compiled Templates (Site specific)
│
├── scripts/            # Utility Scripts (e.g., Backfill)
└── cli.php             # Command Line Entry Point
```

> [!IMPORTANT]
> The `my-data/` directory MUST be blocked from public access. The `www/` directory is the only path that should be exposed by the web server.

## Configuration System
Gaia Alpha uses the `Env` class for centralized configuration. Local environment settings are defined in `my-config.php` (git-ignored).

### Configuration Constants
- `GAIA_DATA_PATH`: Path to data directory (default: `./my-data`)
- `GAIA_DB_PATH`: Path to database file (default: `GAIA_DATA_PATH/database.sqlite`)
- `GAIA_DB_DSN`: PDO DSN string (default: `sqlite:GAIA_DB_PATH`)

### Example Configuration
```php
<?php
define('GAIA_DATA_PATH', __DIR__ . '/my-data');
define('GAIA_DB_PATH', GAIA_DATA_PATH . '/database.sqlite');
define('GAIA_DB_DSN', 'sqlite:' . GAIA_DB_PATH);
// For MySQL:
// define('GAIA_DB_DSN', 'mysql:host=localhost;dbname=gaia');
```

## Application Lifecycle
1. **Request**: All requests to non-static files are routed to `www/index.php`.
2. **Bootstrap**: 
   - `index.php` loads `autoload.php`.
   - Calls `App::web_setup(__DIR__)` (or `cli_setup`):
     - Sets up `Env` variables (`root_dir`, Paths).
     - Loads `my-config.php`.
     - Defines generic steps in `framework_tasks`.
3. **Task Execution (`App::run()`)**:
   - The framework iterates through the configured `framework_tasks` array.
   - **Step 00**: `Debug::init` (Starts timing and memory tracking).
   - **Step 01**: `Response::startBuffer` (Starts robust output buffering).
   - **Step 05**: `Framework::loadPlugins` (Loads active plugins).
   - **Step 06**: `Framework::appBoot` (Generic boot hook).
   - **Step 10**: `Framework::loadControllers` (Instantiates controllers).
   - **Step 12**: `Framework::sortControllers` (Prioritizes routes by Rank).
   - **Step 15**: `Framework::registerRoutes` (Registers routes to Router).
   - **Step 18**: `InstallController::checkInstalled` (Redirects if not installed).
   - **Step 20**: `Router::handle` (Dispatches request to controller).
   - **Step 99**: `Response::flush` (Captures buffer and sends response).
4. **Output Buffering**:
   - We strictly enforce output buffering via `Response::startBuffer`.
   - This prevents premature output (e.g. from `echo`) from breaking header injection.
   - It allows plugins to modify the full response body via the `response_send` hook.
   - Buffering is DISABLED for CLI commands to allow real-time feedback.

- **Benchmarks**: Capable of dispatching 2000+ routes in < 0.2ms.
- **On-Demand Instantiation**: Controllers are now instantiated only when a specific route matches, reducing the overhead of loading multiple plugins.

## Performance & Caching
Gaia Alpha uses a multi-layered caching strategy to maintain sub-10ms response times even with dozens of active plugins.

### 1. Manifest Caching
The framework maintains JSON manifests in `my-data/cache/` to avoid repeated filesystem scans:
- **`plugins_manifest.json`**: Stores paths and context metadata for active plugins.
- **`controllers_manifest.json`**: Maps route prefixes to controller class names.
Regeneration is triggered by `?clear_cache=1` or when the manifests are missing.

### 2. Contextual Loading
To minimize memory usage, code is loaded only when relevant to the current `Request::context()`:
- **`public`**: Guest-facing website and pages.
- **`admin`**: System administration and management (e.g., `/@/admin`).
- **`app`**: User-facing applications and dashboards (e.g., `/@/app`).
- **`api`**: Standardized API endpoints (e.g., `/@/api`).
- **`cli`**: Command-line operations.
Plugins can restrict their loading to specific contexts via their `plugin.json` file.

### 3. Model & Data Caching
- **Static Caching**: Models like `Page` and `DataStore` use in-memory static arrays to cache query results within a single request.
- **File Caching**: Frequently accessed global settings are serialized to `my-data/cache/datastore_{user}_{type}.json`.

## Response Handling
All JSON responses are routed through the `Response` class (`GaiaAlpha\Response`). This centralization allows for consistent formatting and plugin interception.

### Usage
```php
use GaiaAlpha\Response;

// Send data and exit
Response::json(['success' => true], 200);
```

### Hooks
The response lifecycle includes hooks to modify output:
- `response_json_before`: context `['data' => &$data, 'status' => &$status]`
    - Useful for wrapping responses (e.g., standardizing API envelopes) or injecting global metadata.
- `response_send_before`: parameters `$data, $status`
    - Useful for logging or setting final headers.

## Middleware & Pipeline
See [Middleware & Pipeline](middleware.md) for details on the request processing layers.

## Routes Map

| Controller | Use Case | Prefix | Key Routes |
| :--- | :--- | :--- | :--- |
| **Admin** | Dashboard Stats | `/@/admin` | `/stats` |
| **ApiBuilder** | Dynamic APIs | `/@/admin` | `/api-builder` |
| **Auth** | Authentication | `/@/api` | `/login`, `/register`, `/logout`, `/user` |
| **CMS** | Page Management | `/@/api/cms` | `/pages`, `/upload` |
| **Dynamic** | Generated CRUD | `/@/api/v1` | `/{table}` |
| **Form** | Form Builder | `/@/api/forms` | `/forms`, `/public/form` |
| **Map** | Maps & Markers | `/@/api` | `/markers` |
| **Media** | File Serving | `/media` | `/{userId}/{filename}` |
| **Menu** | Navigation | `/@/api` | `/menus` |
| **Partial** | Partial Management | `/@/api/cms` | `/partials` |
| **Plugin** | Plugin Management | `/@/api/admin` | `/plugins` |
| **Settings** | User Preferences | `/@/api/user` | `/settings` |
| **Template** | Template Management | `/@/api/cms` | `/templates` |
| **Todo** | Tasks | `/@/api` | `/todos` |
| **User** | User Management | `/@/api/admin` | `/users` |
| **View** | Frontend Display | `/` | `Home`, `/page/` |

## Media Handling
The `Media` class provides on-demand image processing with caching.

- **Upload Path**: `my-data/uploads/{user_id}/{filename}`
- **Cache Path**: `my-data/cache/{hash}.webp`
- **Supported Formats**: JPEG, PNG, WebP (all converted to WebP for delivery)

### Request Format
```
/media/{userId}/{filename}?w=800&h=600&q=80&fit=contain
```

### Query Parameters
- `w`: Width in pixels
- `h`: Height in pixels
- `q`: Quality (1-100, default: 80)
- `fit`: Resize mode (`contain` or `cover`, default: `contain`)

### Processing Flow
1. **Request**: Client requests `/media/1/image.jpg?w=800`
2. **Cache Check**: System checks if processed version exists in cache
3. **Cache Hit**: Serve cached file with 1-year cache headers
4. **Cache Miss**: 
   - Load source image from uploads
   - Resize/crop according to parameters
   - Convert to WebP
   - Save to cache
   - Serve processed image

### Async Processing
Image processing happens **synchronously on first request** (cache miss), then subsequent requests are served from cache. This approach:
- Avoids complexity of background job queues
- Ensures images are available immediately after processing
- Leverages HTTP caching for performance
- Uses filesystem-based cache invalidation (mtime comparison)

> [!TIP]
> For high-traffic sites, consider pre-generating common sizes during upload or using a CDN with origin caching.

## Database Abstraction
The application uses a custom `LoggedPDO` class that extends PHP's native `PDO`.

### Design Decision: Inheritance vs. Composition
We chose to **inherit from `PDO`** (`class LoggedPDO extends PDO`) rather than wrapping it via composition.

**Rationale:**
1.  **Type Compatibility**: The codebase extensively uses `PDO` type hints (e.g., `Database::getPdo(): PDO`). Changing this would require refactoring 20+ files and potentially introducing a non-standard `DatabaseInterface`.
2.  **Native Hooks**: PHP provides `PDO::ATTR_STATEMENT_CLASS`, which allows us to automatically inject our custom `LoggedPDOStatement` class for all queries. This makes logging prepared statements (`execute()`) trivial without manual wrapper logic.
3.  **Simplicity**: Inheritance allows `LoggedPDO` to be a drop-in replacement, keeping the architecture simple and aligned with PHP's standard library patterns.

While composition is often preferred for loose coupling, in this specific case (a low-level driver wrapper), inheritance provides the most robust and maintainable solution.

### DB Helper Class
The `DB` class (`GaiaAlpha\Model\DB`) provides a centralized interface for database operations with built-in query logging via hooks.

**Design Pattern**: Static helper methods (not inheritance)
- Models define their own methods and call `DB::` static methods
- No inheritance required - models are independent classes
- Consistent with modern PHP practices (explicit dependencies)

**Core Methods**:
```php
// Query execution with logging
DB::query($sql, $params = [])  // Returns PDOStatement

// Data retrieval
DB::fetchAll($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC)
DB::fetch($sql, $params = [])
DB::fetchColumn($sql, $params = [])

// Data modification
DB::execute($sql, $params = [])  // Returns affected row count
DB::lastInsertId()
```

**Query Logging**:
All queries trigger the `database_query_executed` hook with timing information:
```php
Hook::trigger('database_query_executed', [
    'query' => $sql,
    'params' => $params,
    'duration' => $duration
]);
```

**Model Pattern**:
```php
class Page
{
    public static function all() {
        return DB::fetchAll("SELECT * FROM cms_pages ORDER BY id DESC");
    }
    
    public static function find(int $id) {
        return DB::fetch("SELECT * FROM cms_pages WHERE id = ?", [$id]);
    }
    
    public static function create(array $data) {
        DB::execute($sql, $values);
        return DB::lastInsertId();
    }
}
```

**Benefits**:
1. **Centralized Logging**: All database queries are automatically logged for debugging
2. **Explicit Dependencies**: Models clearly show they use `DB::` methods
3. **No Magic**: No hidden inherited behavior - all methods are visible
4. **Flexibility**: Each model can define custom query logic

## Database Schema
The application uses SQLite.

### `users`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `username` | TEXT | Unique |
| `password_hash` | TEXT | Bcrypt hash |
| `level` | INTEGER | 10=Member, 100=Admin |
| `created_at` | DATETIME | |

### `todos`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `user_id` | INTEGER | Owner |
| `title` | TEXT | Content |
| `completed` | INTEGER | 0/1 |
| `parent_id` | INTEGER | Self-reference |
| `labels` | TEXT | Comma-separated |
| `position` | REAL | Sorting order |
| `start_date`| DATETIME | |
| `end_date`  | DATETIME | |
| `color` | TEXT | Hex code |
| `created_at` | DATETIME | |

### `cms_pages`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `user_id` | INTEGER | Owner |
| `title` | TEXT | |
| `slug` | TEXT | Unique URL slug |
| `content` | TEXT | Markdown/HTML |
| `cat` | TEXT | 'page' or custom |
| `template_slug`| TEXT | Reference to `cms_templates` |
| `created_at` | DATETIME | |

### `cms_templates`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `user_id` | INTEGER | Owner |
| `title` | TEXT | |
| `slug` | TEXT | Unique |
| `content` | TEXT | Template structure (JSON) |
| `created_at` | DATETIME | |

### `forms`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `user_id` | INTEGER | Owner |
| `title` | TEXT | |
| `slug` | TEXT | Unique |
| `schema` | TEXT | JSON array of fields |
| `submit_label` | TEXT | Custom button text |
| `created_at` | DATETIME | |

### `form_submissions`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `form_id` | INTEGER | Reference to `forms` |
| `data` | TEXT | JSON object of submission |
| `submitted_at` | DATETIME | |

### `map_markers`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `user_id` | INTEGER | Owner |
| `label` | TEXT | |
| `lat` | REAL | Latitude |
| `lng` | REAL | Longitude |
| `created_at` | DATETIME | |

### `menus`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `title` | TEXT | |
| `location` | TEXT | 'header', 'footer', etc. |
| `items` | TEXT | JSON tree of links |
| `created_at` | DATETIME | |

### `data_store`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER | Primary Key |
| `user_id` | INTEGER | Owner |
| `type` | TEXT | Category (e.g., 'settings') |
| `key` | TEXT | |
| `value` | TEXT | |
| `created_at` | DATETIME | |

## Security Model
- **RBAC**: Implemented in `App.php`. APIs check `$_SESSION['user_level']`.
- **File Access**:
  - `www/` contains NO sensitive data.
  - User uploads are stored in `my-data/uploads` (outside web root).
  - Images are served securely via `Media` class, preventing direct file access and directory traversal.
- **CSRF**: (Planned) Currently relies on Session Auth.

## Frontend Architecture
Gaia Alpha uses a "Bundle-Free" architecture optimized for modern browsers (ES Modules).

### CSS Strategy
- **Global Stylesheet**: `www/css/site.css` serves all application styles.
- **Why?**:
    - **Performance**: The file is extremely small (< 20KB). Splitting it would introduce network request overhead without significant load time improvement.
    - **Simplicity**: No build step (PostCSS, Sass, Webpack) is required.
    - **Maintainability**: Styles are centralized and use CSS Variables for consistent theming.
- **Scaling**: If the file grows significantly (> 100KB), we may reconsider component-scoped CSS or a lightweight build tool.

### Vue App Architecture
The frontend is a lightweight, bundle-free Vue 3 application that relies on native ES Modules.

#### 1. Entry Point (`www/js/site.js`)
- **Mounting**: The app is mounted to the `#app` DOM element.
- **Global State**: Global state (User, Theme, Navigation) is decoupled into `www/js/store.js`.
- **Components**: Core logic connects to the Store, keeping the entry point clean.

#### 2. Component Structure (`www/js/components/`)
- **Single File Components (SFC)**: Written as JS objects exporting a `template` string and `setup()` function.
- **Async Loading**: Heavy components (e.g., `AdminDashboard`, `MapPanel`) are loaded via `defineAsyncComponent`. This ensures the initial page load remains fast (< 100KB) by only fetching code when the user navigates to a specific view.
- **Recursive Components**: Used for complex structures like the `TemplateBuilder` tree and `TodoList` items.

#### 3. State Management
- **Store Pattern**: We use a custom, lightweight Store (`www/js/store.js`) built on Vue's `reactive()` API.
- **Centralized Logic**: Business logic for Auth, Theme, and Navigation resides in the Store actions (`setUser`, `setTheme`).
- **Access**: Components import `store` directly, eliminating the need for complex prop drilling.

#### 4. Reusable Patterns
- **Composables**: Shared logic is extracted into composables.
  - `useCrud`: Standardizes API interactions (Fetch/Create/Update/Delete).
  - `useSorting`: Provides table sorting state and logic.
- **Shared Components**: UI Consistency is maintained via shared components.
  - `Modal`: Standard dialog overlay.
  - `SortTh`: Sortable table header.

#### 5. Routing
- **View-Based Routing**: The app uses a simple state-based router managed by `store.state.currentView`.
- **Navigation**: Switching views updates the store state, triggering the async load of the corresponding component.

#### 5. Theming
- **CSS Variables**: All coloring uses variables defined in `www/css/site.css` (e.g., `var(--bg-color)`, `var(--text-primary)`).
- **Dark/Light Mode**: The app supports dynamic theme switching by toggling a `.light-theme` class on the `<body>` tag, which redefines these variables.

## Code Statistics
*Approximate counts as of v0.34.0*

- **PHP**: ~13,200 lines
- **JavaScript (Application)**: ~9,400 lines
- **JavaScript (Vendor)**: ~28,100 lines
- **CSS**: ~3,300 lines

