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

## Architecture

### Backend (PHP)
- **Server**: Built-in PHP server (`php -S`).
- **Database**: SQLite (`database.sqlite`).
- **Structure**:
  - `class/GaiaAlpha/App.php`: Main application logic and API routing.
  - `class/GaiaAlpha/Database.php`: PDO wrapper for SQLite connection and schema migration.
  - `templates/`: HTML templates (php files).
  - `index.php`: Entry point (Auto-loading).

### Frontend (Vue.js)
- **Framework**: Vue 3 (ES Module build).
- **Components**: Async components loaded on demand.
  - `Login.js`: Authentication form.
  - `TodoList.js`: Protected todo management.
- **State**: Simple reactive state management in `site.js`.

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

## Vue 3 Version Sizes

| Version | Description | Minified | Compiler | Size (approx) |
| :--- | :--- | :---: | :---: | :---: |
| `vue.esm-browser.js` | Full build (for browser) | No | Yes | ~528 KB |
| `vue.esm-browser.prod.js` | Full build (production) | Yes | Yes | ~162 KB |
| `vue.runtime.esm-browser.js` | Runtime-only | No | No | ~364 KB |
| `vue.runtime.esm-browser.prod.js` | Runtime-only (production) | Yes | No | ~103 KB |

Source: [Valid Vue 3 Versions](https://app.unpkg.com/vue@3.5.25/files/dist)