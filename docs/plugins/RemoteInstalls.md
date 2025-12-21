# Remote Installs Management Plugin

## Objective
The **Remote Installs Management** plugin provides a centralized dashboard for managing multiple Gaia Alpha CMS instances. This allows administrators to monitor health, sync plugins, and trigger backups across a distributed network of sites from a single "master" instance.

## Architecture
This plugin leverages the **Model Context Protocol (MCP)** for secure, cross-instance communication. It can operate in two modes:

1.  **Direct (Stdio) Mode**: For managing local instances (e.g., in a dockerized dev environment).
2.  **Remote (SSE) Mode**: For managing production instances over HTTP using Server-Sent Events for real-time feedback.

---

## Configuration
Remote sites are managed via the `my-data/remote_instances.json` configuration file.

### Required Fields
- `id`: Unique identifier for the instance.
- `url`: Full URL of the remote Gaia Alpha install.
- `api_key`: MCP authentication token or JWT secret.
- `label`: Human-readable name (e.g., "Production - New York").

### Example `remote_instances.json`
```json
[
    {
        "id": "prod-us",
        "url": "https://cms.us-east.example.com",
        "api_key": "YOUR_SECURE_TOKEN",
        "label": "Production US-East"
    }
]
```

---

## Key Features & MCP Tools
The plugin extends the MCP server with specialized management tools:

### 1. `remote_health_check`
Triggers the `verify_system_health` tool on the remote instance and aggregates results.
- **Parameters**: `instance_id`

### 2. `sync_plugin_to_remote`
Reads a local plugin directory and pushes it to a remote instance.
- **Parameters**: `instance_id`, `plugin_name`

### 3. `trigger_remote_backup`
Remotely invokes the `backup_site` tool on the target instance and returns the download link or status.
- **Parameters**: `instance_id`

---

## Hooks
- **`auth_session_data`**: Injects the "Remote Manager" menu item for admin users.
- **`framework_load_controllers_after`**: Registers the `RemoteManagerController` for UI interactions.

---

## Admin UI Components
The plugin includes a Vue.js component (`RemoteManager.js`) located in `resources/js/components/`.

### Panels
- **Instance Overview**: Status indicators (Green/Yellow/Red) for all registered sites.
- **Sync Console**: Log of plugin synchronization operations.
- **Task Runner**: Interface to trigger remote backups or clears caches.

---

## Security Considerations
> [!CAUTION]
> Remote management requires high-privilege access. Ensure that `api_key` values are encrypted at rest and that the remote server's `McpServer` is strictly restricted to IP-whitelisted or JWT-authenticated requests.

### Best Practices
- Use HTTPS for all remote communication.
- Rotate API keys regularly.
- Limit the permissions of the management agent on the remote site (RBAC).
