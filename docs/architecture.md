# System Architecture

## Overview
Gaia Alpha is a lightweight PHP framework designed for simplicity and security. It separates public assets from private application logic and data.

## Directory Structure
The application follows a strict separation between public-facing code and private logic.

```
gaia-alpha/
├── www/                # Public Web Root (Served by Web Server)
│   ├── index.php       # Frontend Entry Point
│   ├── js/             # Vue.js Application
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
│       ├── Cli.php     # CLI Tool Logic (Static)
│       ├── Controller/ # Request Controllers (Auth, Cms, etc.)
│       └── Model/      # Data Models (User, Page, etc.)
│
├── templates/          # PHP Templates (HTML Views)
│
├── my-data/            # PRIVATE Data (GitIgnored)
│   ├── database.sqlite # SQLite Database
│   ├── api-config.json # API Builder Configuration
│   ├── uploads/        # Raw User Uploads
│   └── cache/          # Processed Image Cache
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
   - Calls `App::web_setup(__DIR__)`:
     - Sets up `Env` variables (`root_dir`,Paths).
     - Loads `my-config.php`.
     - Defines framework tasks.
3. **Execution (`App::run()`)**:
   - Iterates through defined tasks:
     - `Framework::loadControllers`: Dynamically instantiates controllers.
     - `Framework::registerRoutes`: Collects routes from all controllers.
     - `Router::handle`: Dispatches the request to the matching handler.
4. **Routing**:
   - **API Requests**: `/api/...` -> Mapped Controller Method.
   - **Media Requests**: `/media/{userId}/{filename}` -> `MediaController`.
   - **Page Requests**: Default -> Renders templates (Vue App host).

## Routing Strategy
The framework employs a two-tiered strategy to manage route priority and prevent conflicts between API endpoints and frontend "catch-all" routes.

### 1. Controller Ranking
Controllers are loaded dynamically by `Framework`. To ensure specific API controllers take precedence over generic frontend controllers (like `ViewController`), a ranking system is used:
- **`BaseController::getRank()`**: Returns an integer priority (Default: `10`).
- **`Framework::sortControllers()`**: Sorts controllers by rank (ascending) before registering routes.

**Example**:
- `DbController` (Rank 10): Registers `/api/admin/...` first.
- `ViewController` (Rank 100): Registers catch-all `.*` routes last.

### 2. Route Definition Order
Within a single controller, routes are matched in the order they are defined.
**Best Practice**: Define specific paths *before* wildcard patterns.
```php
// Correct
Router::get('/api/users/me', ...);
Router::get('/api/users/(\d+)', ...);
```

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
