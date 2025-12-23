# AI Constitution

This document contains the foundational directives and "unbreakable rules" for AI agents (like Antigravity) working on the Gaia Alpha project. All changes must align with this constitution.

## 1. Core Directives

### Architecture & Patterns
- **Monolithic Core, Plugin Extension**: Never modify the core framework logic (found in `class/GaiaAlpha/`) if a feature can be implemented as a plugin.
- **Zero-Build Native**: Favor vanilla PHP and JavaScript. Avoid adding compilation steps (like Webpack or Babel) unless strictly requested by the USER.
- **Hook Integration**: Use `Hook::run()` and `Hook::add()` for cross-plugin communication.
- **Database Portability**: Always use `GaiaAlpha\Model\DB` for queries. Follow the [Multi-DB SQL Pattern](../core/multi_db_sql.md).

### Code Style
- **PSR-4 Compliance**: All PHP classes must follow PSR-4 namespacing relative to their `class/` folder.
- **Minimal Dependencies**: Before adding a new library, check if the logic can be implemented concisely with existing core helpers.
- **Documentation**: Every new plugin MUST include a `docs/plugins/[plugin_name].md` file.

## 2. Forbidden Zones

Do not modify these files/directories without explicit confirmation from the USER:
- `.env` (Sensitive configuration)
- `templates/sql/` (Core schema - use migrations in `templates/sql/migrations/` instead)
- `my-data/` (Actual user data)

## 3. Reference Hierarchy

When resolving "How should I implement this?", prioritize documentation in this order:
1.  **docs/patterns/**: The gold standard for code structure.
2.  **docs/ai/**: This folder (Operating environment).
3.  **Core Code**: Direct observation of `class/GaiaAlpha/`.
4.  **docs/core/**: Historical architecture notes.

## 4. Error Handling
- Never silence errors with `@`.
- Use `try/catch` and log errors to `\GaiaAlpha\Debug` if present.
- Return meaningful JSON error responses (status 400/500) for API failures.
