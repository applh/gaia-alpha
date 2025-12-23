# Project Map (AI Context)

This map provides a functional overview of Gaia Alpha to help agents navigate the codebase efficiently.

## 📦 System Overview

| Subsystem | Root Directory | Purpose |
| :--- | :--- | :--- |
| **Framework Core** | `class/GaiaAlpha/` | Kernel, Router, Auth, Session, Database. |
| **Active Data** | `my-data/` | User uploads, SQLite DB, and local config. |
| **Plugins** | `plugins/` | All modular features (FileExplorer, Analytics, etc). |
| **Templates** | `templates/` | PHP view files and SQL schemas. |
| **Assets** | `resources/` | Global CSS, JS, and vendor libraries. |

## 🛠️ Entry Points

- **HTTP Index**: `index.php` (Boots framework and dispatches router).
- **CLI Kernel**: `cli.php` (Entry point for command-line tasks).
- **Autoloader**: `class/GaiaAlpha/Framework.php` (Dynamic class discovery).
- **Hook Hub**: `class/GaiaAlpha/Hook.php` (Event system).

## 🧩 Key Plugin Patterns

Most plugins follow this standard structure:
- `index.php`: Hook registrations and UI menu injection.
- `class/Controller/`: API endpoints.
- `class/Service/`: Business logic.
- `resources/js/`: Vue components.

## 🚦 Critical Routes

- `/@/` -> Admin API prefix.
- `/f/` -> Public Form handler.
- `/v/` -> Built-in View renderer.
