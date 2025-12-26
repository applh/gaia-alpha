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

- **Content & SEO**:
  - `list_pages`: Lists pages for a specific site.
  - `get_page`: Retrieves full content and metadata for a page by slug.
  - `upsert_page`: Creates or updates a page (automatically archives versions).
  - `analyze_seo`: Automatically analyze a page's SEO score and suggests improvements.
  - `bulk_import_pages`: Import multiple pages from JSON or CSV.
- **Media & AI**:
  - `list_media`: Lists files in a site's media directory.
  - `ai_generate_image`: Simulate AI image generation and save directly to site assets.
- **Sites & Administration**:
  - `list_sites`: Lists all managed sites (default and sub-sites).
  - `create_site`: Creates a new site domain with a pre-configured admin user.
  - `backup_site`: Generates a ZIP backup of a site's database and assets.
  - `search_plugins`: Search for available and installed plugins.
- **Developer Tools**:
  - `db_query`: Executes read-only SQL queries on a site's database.
  - `read_log`: Reads the latest system log entries.
  - `verify_system_health`: Checks directory permissions and database connectivity.
  - `install_plugin`: Installs a new plugin.
  - `generate_template_schema`: Suggest template metadata based on natural language description.
  - `db_migration_assistant`: Suggest SQL migration scripts based on schema change description.
  - `list_routes`: Lists all registered routes in the system (static and dynamic).
  - `debug_route`: Test a URL path against the router to see match details.
  - `run_console_command`: Execute internal console commands (e.g. `cache:clear`).
  - `run_tests`: Run the project test suite or specific tests.

## Available Resources

Agents can access real-time data through these resources:

- `cms://sites/list`: JSON list of all available sites.
- `cms://system/logs`: Full content of the system log.
- `cms://sites/{site}/database/tables`: Complete table definitions and record counts for any site.
- `cms://sites/{site}/pages/{slug}/versions`: List historical versions of a specific page.
- `cms://templates/list`: List all available PHP templates.
- `cms://components/list`: List all available JavaScript UI components.

## Prompts

Reusable prompt templates are available via the `prompts/list` and `prompts/get` MCP endpoints. 

### Specialized Role Prompts:
- `seo_specialist(slug)`: Instructions for a specialized SEO audit of a page.
- `security_auditor`: Instructions for auditing system security and logs.
- `ui_designer(path)`: Instructions for evaluating UI components and templates.
- `summarize_page(slug)`: Instructs the agent to provide a professional summary of a specific page.
- `summarize_health`: Summarize the current system status and any active errors.

## Troubleshooting

-   **"Unknown command group: mcp"**: Ensure you have enabled the `McpServer` plugin (it should be auto-loaded as a Core Plugin).
-   **JSON Errors**: Ensure no other script or `echo` statement is outputting text to STDOUT. The MCP channel must be pure JSON.
