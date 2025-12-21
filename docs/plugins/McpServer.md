# McpServer Plugin

## Objective
The **McpServer** plugin implements the **Model Context Protocol (MCP)**, allowing external AI agents to interact with the Gaia Alpha CMS. It provides a standardized interface for agents to discover and execute tools, read resources, and manage prompts.

## Configuration
- **Type**: `core`
- **Enabled**: Always enabled for agent interaction.

## Hooks
- **`cli_resolve_command`**: Registers the `mcp-server` CLI command.
- **`framework_load_controllers_after`**: Registers the `SseController` for handling SSE transport.

## CLI Commands
- `php gaia mcp-server`: Starts the MCP server in Stdio mode.

## Components
- **Server**: Handles JSON-RPC requests and dispatches them to tools and resources.
- **SseController**: Handles Server-Sent Events (SSE) for HTTP-based MCP transport.
- **Tools**: located in `class/Tool/`.
- **Resources**: located in `class/Resource/`.
