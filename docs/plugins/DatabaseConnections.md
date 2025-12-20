# Database Connections Plugin

This plugin allows managing external database connections (MariaDB, PostgreSQL, SQLite) from the Gaia Alpha admin panel and providing them to AI agents via MCP.

## Objective
To enable the system and AI agents to connect to, query, and manage multiple external databases without modifying the core configuration.

## Configuration
No environment variables are required. All connections are stored in the local `cms_db_connections` SQLite table.

## Features

### Admin UI
Located at `/admin/db-connections`.
- **List/Create/Edit/Delete** connections
- **Test Connection** button to verify credentials
- **Query Runner** to execute raw SQL against any configured connection

### MCP Tools
This plugin exposes the following tools to the MCP server:

1.  **`list_db_connections`**
    - Lists all available connections (passwords masked).
    - Use this to find the `id` of a connection.

2.  **`test_db_connection`**
    - Verifies if a connection is active.
    - Can test an existing ID or raw parameters.

3.  **`execute_external_query`**
    - Executes SQL against a specific connection.
    - Argument: `connection_id` (int), `query` (string)
    - Returns JSON results for SELECT, affected rows for others.

## Supported Databases
- **MySQL / MariaDB**: Verified working. Requires host, port (3306), database, user, password.
- **PostgreSQL**: Verified working. Requires host, port (5432), database, user, password.
- **SQLite**: Local file path.

## Security
- Credentials are stored in the local SQLite database.
- Admin access (level 100) is required for all API endpoints.
- Passwords are never sent back in list responses.
