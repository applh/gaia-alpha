# McpServer Plugin

## Objective
The **McpServer** plugin implements the **Model Context Protocol (MCP)**, allowing external AI agents to interact with the Gaia Alpha CMS. It provides a standardized interface for agents to discover and execute tools, read resources, and manage prompts.

The implementation is fully **JSON-RPC 2.0 compliant**, correctly handling notifications and initialization handshakes as per the MCP specification.

## Configuration
- **Type**: `core`
- **Enabled**: Always enabled for agent interaction.

## Hooks
- **`cli_resolve_command`**: Registers the `mcp-server` CLI command.
- **`framework_load_controllers_after`**: Registers `SseController` and `McpStatsController`.
- **`auth_session_data`**: Injects the "MCP Activity" menu item into the Admin sidebar.

## CLI Commands
- `php gaia mcp-server`: Starts the MCP server in Stdio mode.

## Components
- **Server**: Handles JSON-RPC requests and dispatches them to tools and resources.
- **SseController**: Handles Server-Sent Events (SSE) for HTTP-based MCP transport.
- **McpStatsController**: Serves activity statistics for the Admin UI.
- **McpLogger**: Service for recording request/response activity in the database.
- **McpStatsService**: Service for aggregating KPI data from logs.
- **McpDashboard**: Vue-based Admin UI component for monitoring MCP activity and performance.
- **Tools**: located in `class/Tool/`.
- **Resources**: located in `class/Resource/`.
