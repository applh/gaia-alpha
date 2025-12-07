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
│       ├── App.php     # Core Application Logic
│       ├── Router.php  # Request Routing
│       ├── Database.php# Database Wrapper
│       ├── Media.php   # Image Processing API
│       ├── Cli.php     # CLI Tool Logic
│       ├── Controller/ # Request Controllers (Auth, Cms, etc.)
│       └── Model/      # Data Models (User, Page, etc.)
│
├── templates/          # PHP Templates (HTML Views)
│
├── my-data/            # PRIVATE Data (GitIgnored)
│   ├── database.sqlite # SQLite Database
│   ├── uploads/        # Raw User Uploads
│   └── cache/          # Processed Image Cache
│
├── scripts/            # Utility Scripts (e.g., Backfill)
└── cli.php             # Command Line Entry Point
```

> [!IMPORTANT]
> The `my-data/` directory MUST be blocked from public access. The `www/` directory is the only path that should be exposed by the web server.

## Application Lifecycle
1. **Request**: All requests to non-static files are routed to `www/index.php`.
2. **Bootstrap**: `index.php` initializes the autoloader.
3. **App Initialization**: `GaiaAlpha\App` is instantiated.
   - Connects to SQLite database in `my-data/`.
   - Initializes `Media` handler with `my-data/` paths.
4. **Routing (`App->run()`)**:
   - **API Requests**: `/api/...` -> `handleApi()`
   - **Media Requests**: `/media/...` -> `media->handleRequest()`
   - **Page Requests**: Default -> Renders `templates/public_home.php` (Vue App host)

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
| `created_at` | DATETIME | |

## Security Model
- **RBAC**: Implemented in `App.php`. APIs check `$_SESSION['user_level']`.
- **File Access**:
  - `www/` contains NO sensitive data.
  - User uploads are stored in `my-data/uploads` (outside web root).
  - Images are served securely via `Media` class, preventing direct file access and directory traversal.
- **CSRF**: (Planned) Currently relies on Session Auth.
