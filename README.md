# GAIA: GeoDynamic Artificial Intelligence Applications

Gaia Alpha is a lightweight, self-contained web application framework demonstrating a PHP backend with a Vue.js frontend, using SQLite for data persistence.


## Repository Information

- **GitHub Repo**: [https://github.com/applh/gaia-alpha](https://github.com/applh/gaia-alpha)
- **SSH Key**: A custom SSH key has been generated for this repository.
  - **Key File**: `~/.ssh/id_ed25519_github`
  - **Usage**: To push/pull without password, ensure your local git config uses this key:
    ```bash
    git config core.sshCommand "ssh -i ~/.ssh/id_ed25519_github -o IdentitiesOnly=yes"
    ```
  - **Generation Command**:
    ```bash
    ssh-keygen -t ed25519 -C "gaia-alpha-dev" -f ~/.ssh/id_ed25519_github -N ""
    ```

## Architecture

For detailed information, please refer to:
- [System Architecture](docs/architecture.md): Lifecycle, directory structure, and database schema.
- [API Documentation](docs/api.md): Media API and JSON endpoints.
- [Security Comparison](docs/security_comparison.md): Analysis of security features.

### Backend (PHP)
- **Server**: Built-in PHP server (`php -S`).
- **Database**: SQLite (`my-data/database.sqlite`).
- **Media**: Secure image processing via `GaiaAlpha\Media`.

### Frontend (Vue.js)
- **Framework**: Vue 3 (ES Module build).
- **Entry**: `www/index.php` serves the initial HTML.

## User Manual

### Installation & Run
1. Ensure you have PHP 8.0+ installed.
2. Clone the repository.
3. Start the local server:
   ```bash
   php -S localhost:8000 -t www
   ```
4. Open [http://localhost:8000](http://localhost:8000) in your browser.

### Usage
1. **Register**: On the home page, toggle to "Register" mode. Enter a username and password.
2. **Login**: Use your credentials to log in.
3. **Manage Todos**:
   - **Add**: Type a task in the input box and press Enter or click "Add".
   - **Complete**: Click on a todo item to toggle its completed status (strikethrough).
   - **Delete**: Click the red "x" button to remove a todo.
4. **Logout**: Click the "Logout" button in the header.

### User Levels & Roles
Gaia Alpha supports Role-Based Access Control (RBAC):
- **Member (Level 10)**: Default role. Access to personal Todo List and CMS.
- **Admin (Level 100)**: Access to Admin Dashboard, User Management, and personal Todos.

#### Admin Features
- **Dashboard**: View system stats (total users, total todos).
- **User Management**: View list of registered users and their levels.
- **Login/Register Toggle**: Header buttons allow switching between Login and Register modes when logged out.

## CLI Tool
Gaia Alpha includes a command-line tool (`cli.php`) for database management.

**Usage:** `php cli.php <command> [arguments]`

**Commands:**
- `table:list <table>`: List all rows in a table.
- `table:insert <table> <json_data>`: Insert a row.
- `table:update <table> <id> <json_data>`: Update a row.
- `table:delete <table> <id>`: Delete a row.
- `sql <query>`: Execute a raw SQL query.
- `media:stats`: Show storage stats for uploads and cache.
- `media:clear-cache`: Clear all cached images.
- `help`: Show usage instructions.

**Examples:**
```bash
php cli.php table:list users
php cli.php media:stats
php cli.php sql "SELECT count(*) FROM todos"
```

## Vue 3 Version Sizes

| Version | Description | Minified | Compiler | Size (approx) |
| :--- | :--- | :---: | :---: | :---: |
| `vue.esm-browser.js` | Full build (for browser) | No | Yes | ~528 KB |
| `vue.esm-browser.prod.js` | Full build (production) | Yes | Yes | ~162 KB |
| `vue.runtime.esm-browser.js` | Runtime-only | No | No | ~364 KB |
| `vue.runtime.esm-browser.prod.js` | Runtime-only (production) | Yes | No | ~103 KB |

Source: [Valid Vue 3 Versions](https://app.unpkg.com/vue@3.5.25/files/dist)