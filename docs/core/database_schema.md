# Database Schema

This document provides an overview of the Gaia Alpha database schema.

> [!NOTE]
> This document is currently a placeholder. The database schema is dynamically managed via migrations in `templates/sql/migrations/`.

## Core Tables
- `cms_users`: User accounts and roles.
- `cms_settings`: Global application settings.
- `cms_pages`: Publicly accessible pages.
- `cms_nodes`: Content nodes for pages.

## Plugin Tables
Each plugin may define its own tables via a `schema.sql` file in the plugin directory.

For more details on database management, see **[Multi-DB SQL Management](multi_db_sql.md)**.
