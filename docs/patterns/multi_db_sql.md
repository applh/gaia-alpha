# Multi-DB SQL Management Pattern

Gaia Alpha supports multiple database backends: **SQLite**, **MariaDB/MySQL**, and **PostgreSQL**. To ensure compatibility across these drivers without writing three versions of every query, we follow a specific pattern of SQL abstraction.

## The "Gaia-standard" SQL Dialect

When writing schema files (`.sql`) or raw queries in PHP, always use the **SQLite-compatible dialect**. This is our "lingua franca" that the application translates at runtime for other databases.

### Key Rules:
1.  **Primary Keys**: Use `INTEGER PRIMARY KEY AUTOINCREMENT`.
2.  **Date/Time**: Use `DATETIME` or `DATETIME DEFAULT CURRENT_TIMESTAMP`.
3.  **Booleans**: Use `TINYINT(1)` (normalized to `SMALLINT` in Postgres).
4.  **Strings**: Use `TEXT` or `VARCHAR(255)`.
5.  **Comments**: Use `--` for single-line comments.

## The Translation Layer

The `GaiaAlpha\Database` class handles the heavy lifting via the `transformSql()` method.

### Handled Translations:
| Source (SQLite-ish) | Target (MySQL) | Target (PostgreSQL) |
| :--- | :--- | :--- |
| `INTEGER PRIMARY KEY AUTOINCREMENT` | `INT AUTO_INCREMENT PRIMARY KEY` | `SERIAL PRIMARY KEY` |
| `DATETIME` | `DATETIME` | `TIMESTAMP` |
| `TINYINT` | `TINYINT` | `SMALLINT` |

> [!IMPORTANT]
> Always execute queries through the `GaiaAlpha\Model\DB` class or the `import()` method of `GaiaAlpha\Database`. These classes automatically apply the translation layer.

## Schema Management

New schema definitions should be placed in `templates/sql/*.sql`. Migrations should be placed in `templates/sql/migrations/*.sql`.

- Files are executed in alphabetical order.
- The `Database::ensureSchema()` method iterates through these files and applies translations driver-by-driver.
- **Robust Splitting**: Statements are split using `splitSql()`, which safely handles semicolons within quoted strings (e.g., HTML content in a `page_content` column).

## Backup and Restore

Gaia Alpha provides driver-agnostic backup and restore via the CLI and API.

### How it works:
1.  **Export (`dump`)**: Iterates through all tables, reconstructs a "Gaia-standard" `CREATE TABLE` statement by inspecting table meta-data, and exports records.
2.  **Import (`import`)**: Reads a Gaia-standard SQL file and applies it to the current database, regardless of the target driver.

### CLI Usage:
```bash
# Backup the database to a file
php cli.php db:export my_backup.sql

# Restore a database from a file
php cli.php db:import my_backup.sql
```

## Best Practices

1.  **Avoid Dialect-Specific Functions**: Don't use `IFNULL` (MySQL/SQLite) vs `COALESCE` (Postgres) if you can avoid it. Stick to standard SQL.
2.  **Inspect with getTableSchema()**: If you need to check table structure at runtime, use `DB::getTableSchema($table)`, which returns a normalized array of column info.
3.  **Quote Values**: Always use prepared statements (`DB::query($sql, $params)`) or `Database::quote()` for raw values in manual dumps.
