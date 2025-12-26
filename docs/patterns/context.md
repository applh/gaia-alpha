# Application Context System

Gaia Alpha uses a granular context system to determine the purpose of a request and filter available functionality accordingly.

## Context Types

| Context | Purpose | Default Prefix |
|---------|---------|----------------|
| `public` | Guest-facing website and pages | `/` |
| `admin`  | System administration and management | `/@/admin` |
| `app`    | User-facing applications and dashboards | `/@/app` |
| `api`    | Standardized API endpoints | `/@/api` |
| `cli`    | Command-line interface operations | N/A |
| `worker` | Background tasks and job processing | N/A |

## Detection Logic

The context is determined in `Request::context()` using the following priority:

1. **CLI**: Detected via `php_sapi_name() === 'cli'`.
   - *Override*: In automated tests, define `GAIA_TEST_HTTP` to bypass CLI detection and test URL-based logic.
2. **System Prefixes**: Checks if the current path starts with configurable system prefixes.
3. **Public**: Defaults to `public` if no other context matches.

## Configuration

Context prefixes are configurable via `Env` variables, allowing for white-labeling or path customization:

```php
// In config.php
\GaiaAlpha\Env::set('admin_prefixes', ['/management', '/@/admin']);
\GaiaAlpha\Env::set('api_prefixes', ['/v1/api', '/@/api']);
```

## Usage

Context is primarily used for:
- **Plugin Loading**: Load only necessary plugins for the current context (e.g., don't load admin tools in a public context).
- **Error Handling**: The `Router` returns JSON errors for `api` and `admin` contexts instead of HTML 404 pages.
- **Access Control**: Controllers can use context-aware checks for more granular security.
