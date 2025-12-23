# Feature Roadmap to Reproduce

This roadmap guides the AI to build the specific features that define the current product, in the optimal order of dependency.

## Phase 1: The Core Platform
1.  **Lightweight Kernel**: `index.php`, `App.php` (no complex frameworks).
2.  **Plugin Architecture**: Custom Autoloader, `plugin.json` parsing.
3.  **Routing System**: Simple Regex-based router in `class/GaiaAlpha/Router.php`.
4.  **Database Layer**: PDO wrapper (`GaiaAlpha\Database`) + `Env` integration.
5.  **User System**: `users` table, password hashing, and session management.

## Phase 2: Essential Plugins (The "System" Plugins)
*Prompt these individually as "Core Plugins" that live in `plugins/`.*

1.  **Console (CLI)**:
    -   Ability to run commands via terminal (`php cli.php`).
    -   Command discovery from other plugins.
    -   Key commands: `migrate`, `user:create`.
2.  **Database Manager**:
    -   Web-based UI to browse tables and rows.
    -   Crucial for debugging without external tools.
3.  **MultiSite**:
    -   Isolating data per domain mapping.
    -   Middleware to switch `Env::get('path_data')` based on `$_SERVER['HTTP_HOST']`.

## Phase 3: The "Killer Features" (Builder Tools)
1.  **Component Builder**:
    -   UI to create HTML/CSS snippets.
    -   Save to `my-data/components`.
    -   Shortcode parser to render them `[[component:name]]`.
2.  **API Builder**:
    -   UI to define SQL queries and expose them as JSON endpoints.
    -   Dynamic route generation for these APIs.
3.  **Site Import/Export**:
    -   Zipping up the `my-data` folder.
    -   Importing logic to unzip and restore database/assets.

## Phase 4: Developer Experience & UI
1.  **Design System**:
    -   "Dark Mode" by default.
    -   Glassmorphism (backdrop-blur).
    -   Mobile-first responsive layout (Sidebar logic).
2.  **Code Editor Integration**:
    -   Embedding `AceEditor` or generic `textarea` with syntax highlighting for the builders.

## Phase 5: AI Integration (The "Antigravity" Layer)
1.  **Prompt Guide**: Documentation on how to use AI to extend the system (Recursive: "A system that documents itself").
