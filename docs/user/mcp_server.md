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

The server currently exposes the following tools:

-   `system_info`: Returns the current CMS version and PHP version.

*(More tools like `site_list`, `page_read`, and `db_query` will be added in upcoming releases.)*

## Troubleshooting

-   **"Unknown command group: mcp"**: Ensure you have enabled the `McpServer` plugin (it should be auto-loaded as a Core Plugin).
-   **JSON Errors**: Ensure no other script or `echo` statement is outputting text to STDOUT. The MCP channel must be pure JSON.
