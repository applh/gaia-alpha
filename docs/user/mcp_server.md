# Using the Gaia Alpha MCP Server

The **Model Context Protocol (MCP)** plugin allows external AI agents to directly interact with your Gaia Alpha CMS. By connecting an agent to this server, you enable it to:
- Read and manage site content
- Query the database
- Perform system administration tasks
- Access shared prompt libraries

## Connection Details

To connect any MCP-compatible client, you need to provide the command to run the server.

- **Command**: `php`
- **Arguments**: `[absolute-path-to-cli.php] mcp:server`
- **Working Directory**: `[absolute-path-to-project-root]`

## Tutorials

### 1. Antigravity

To enable Antigravity to manage your CMS, you need to add the MCP server definition to your project configuration or agent settings.

**Configuration:**
Add the following to your MCP settings:

```json
{
  "mcpServers": {
    "gaia-cms": {
      "command": "php",
      "args": [
        "/absolute/path/to/project/cli.php",
        "mcp:server"
      ],
      "env": {
        "GAIA_ENV": "production"
      }
    }
  }
}
```

Once configured, you can ask Antigravity:
> "Check the system info of my CMS."
> "List all sites in the database."

### 2. Claude Desktop

To use the CMS with the Claude Desktop app:

1.  Open your config file:
    -   **Mac**: `~/Library/Application Support/Claude/claude_desktop_config.json`
    -   **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
2.  Add the server definition:

```json
{
  "mcpServers": {
    "gaia-cms": {
      "command": "php",
      "args": [
        "/Users/username/path/to/gaia-alpha/cli.php",
        "mcp:server"
      ]
    }
  }
}
```
3.  Restart Claude Desktop.
4.  You will see a 🔌 icon indicating the connection.

### 3. Cursor (and others) API

For agents that connect via HTTP (SSE), you will need to expose the web server endpoint.

*Note: The current implementation primarily supports Stdio (Command Line) transport. HTTP/SSE support is planned for future updates.*

## Available Tools

The server currently exposes the following tools for content and system management:

- **System**:
  - `system_info`: Returns the current CMS version and PHP version.
  - `verify_system_health`: Checks directory permissions and database connectivity.
- **Sites**:
  - `list_sites`: Lists all managed sites (default and sub-sites).
  - `create_site`: Creates a new site domain with a pre-configured admin user.
  - `backup_site`: Generates a ZIP backup of a site's database and assets.
- **Content**:
  - `list_pages`: Lists pages for a specific site.
  - `get_page`: Retrieves full content and metadata for a page by slug.
  - `upsert_page`: Creates or updates a page.
  - `list_media`: Lists files in a site's media directory.
- **Data & Ops**:
  - `db_query`: Executes read-only SQL queries on a site's database.
  - `read_log`: Reads the latest system log entries.
  - `install_plugin`: Installs a new plugin.

## Available Resources

Agents can access real-time data through these resources:

- `cms://sites/list`: JSON list of all available sites.
- `cms://system/logs`: Full content of the system log.
- `cms://sites/{site}/database/tables`: Complete table definitions and record counts for any site.

## Prompts

Reusable prompt templates are available via the `prompts/list` and `prompts/get` MCP endpoints. 
- Example: `summarize_page(slug)` - Instructs the agent to provide a professional summary of a specific page.

## Troubleshooting

-   **"Unknown command group: mcp"**: Ensure you have enabled the `McpServer` plugin (it should be auto-loaded as a Core Plugin).
-   **JSON Errors**: Ensure no other script or `echo` statement is outputting text to STDOUT. The MCP channel must be pure JSON.
