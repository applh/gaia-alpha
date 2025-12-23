# Database Selection Support Plan

This document outlines the plan to allow users to choose between SQLite, MariaDB/MySQL, and PostgreSQL during the Gaia Alpha installation process.

## 1. Architecture Changes

The current `GaiaAlpha\Model\DB` class already supports generic PDO connections via the `GAIA_DB_DSN` constant. The transition to multi-database support will primarily involve configuration management rather than deep core refactoring.

### Configuration Strategy
Instead of putting `my-config.php` in the project root, we will place it in `my-data/config.php`. This ensures that all site-specific state (database, uploads, configuration) is contained within a single directory, which greatly simplifies Docker volume mounting and backup strategies.

**Bootstrapping Logic Change:**
`GaiaAlpha\App` will be updated to check for configuration in the following order:
1. `my-data/config.php` (Preferred)
2. `my-config.php` (Legacy/Root)

**Config Content:**
```php
// SQLite (Default)
define('GAIA_DB_DSN', 'sqlite:' . __DIR__ . '/database.sqlite'); // Relative to my-data/


// MySQL / MariaDB
define('GAIA_DB_DSN', 'mysql:host=localhost;port=3306;dbname=gaia_alpha;charset=utf8mb4');
define('GAIA_DB_USER', 'user');
define('GAIA_DB_PASS', 'password');

// PostgreSQL
define('GAIA_DB_DSN', 'pgsql:host=localhost;port=5432;dbname=gaia_alpha');
define('GAIA_DB_USER', 'user');
define('GAIA_DB_PASS', 'password');
```

The `DB` class will need slight modification to use `GAIA_DB_USER` and `GAIA_DB_PASS` when connecting, as SQLite doesn't strictly require them.

## 2. Installation Flow

We will modify the installation screen (`templates/install.php`) to include a "Database Setup" section.

### UI Changes
- **Database Type Dropdown**: [SQLite (Default), MySQL/MariaDB, PostgreSQL]
- **Connection Details** (Hidden if SQLite is selected):
    - Host (default: 127.0.0.1)
    - Port (default: 3306 or 5432)
    - Database Name
    - Username
    - Password

### Backend Logic (`InstallController`)
- Validate connection capabilities (e.g., check if `pdo_mysql` or `pdo_pgsql` extensions are loaded).
- Attempt a test connection before proceeding.
- Write the configuration to `my-config.php`.
- Run schema creation scripts compliant with the selected dialect.

## 3. Performance Comparison

| Feature | SQLite | MariaDB / MySQL | PostgreSQL |
| :--- | :--- | :--- | :--- |
| **Best For** | Embedded, Dev, Low-Medium Traffic | Standard Web Hosting, General Purpose | Complex Data, Enterprise, High Concurrency |
| **Setup** | Zero config (just a file) | Requires server process | Requires server process |
| **Write Concurrency** | Low (File locking) | High (Row-level locking) | Very High (MVCC) |
| **Read Speed** | Extremely Fast (No network overhead) | Fast | Fast |
| **JSON Support** | Good (JSON1 extension) | Good (Native JSON type) | Excellent (JSONB, Indexing) |
| **Memory Footprint** | Low | Medium | High |

**Recommendation:**
- **SQLite**: Keep as the default for rapid development and simple deployments.
- **MariaDB/MySQL**: Recommended for standard production sites on shared hosting or VP.
- **PostgreSQL**: Recommended for complex applications or heavy write loads.

## 4. Docker Setup

To support these databases in a containerized environment, we provide a `docker-compose.yml` reference.

### Docker Compose Configuration

```yaml
version: '3.8'

services:
  # Gaia Alpha App
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - GAIA_DB_TYPE=mysql # or pgsql, sqlite
      - GAIA_DB_HOST=db
      - GAIA_DB_NAME=gaia
      - GAIA_DB_USER=gaia_user
      - GAIA_DB_PASS=secret
    volumes:
      - ./my-data:/var/www/html/my-data
    depends_on:
      - db

  # Pick ONE of the database services below:

  # Option A: MariaDB
  db:
    image: mariadb:10.11
    environment:
      MYSQL_ROOT_PASSWORD: root_secret
      MYSQL_DATABASE: gaia
      MYSQL_USER: gaia_user
      MYSQL_PASSWORD: secret
    volumes:
      - db_data:/var/lib/mysql

  # Option B: PostgreSQL
  # db:
  #   image: postgres:15
  #   environment:
  #     POSTGRES_DB: gaia
  #     POSTGRES_USER: gaia_user
  #     POSTGRES_PASSWORD: secret
  #   volumes:
  #     - db_data:/var/lib/postgresql/data

volumes:
  db_data:
```

### Environment Integration
The `my-data/config.php` approach works perfectly with Docker. You simply mount a volume to `/var/www/html/my-data`.
- If the volume is empty, the installer runs and creates `my-data/config.php`.
- If the volume has data, the app detects `my-data/config.php` and boots.

This is cleaner than mounting a file to the root directory or relying solely on ENV vars for complex configs.

## 5. Implementation Status

The Multi-Database support is now a core feature of Gaia Alpha.

- **Completed**: Refactored `DB` class for driver-agnostic connections.
- **Completed**: Updated `InstallController` for guided database setup.
- **Completed**: Implemented `Database::transformSql()` for automatic dialect translation.
- **Completed**: Implemented driver-agnostic `dump()` and `import()` for backups.
- **Completed**: Added [Multi-DB SQL Management Pattern](../core/multi_db_sql.md).

For development guidelines, refer to the [Multi-DB SQL Management Pattern](../core/multi_db_sql.md) document.
