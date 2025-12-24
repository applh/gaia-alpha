# Audit Trail Plugin

The Audit Trail plugin provides immutable logging of administrative and API actions within the Gaia Alpha framework. It tracks "who did what, when, and from where" to ensure accountability and aid in troubleshooting.

## Features

- **Automatic Logging**: Captures all `INSERT`, `UPDATE`, and `DELETE` operations via database hooks.
- **Contextual Awareness**: Logs the actor (User ID), action type, resource type, and resource ID.
- **Payload Capture**: Stores the JSON payload of requests (sanitized for sensitive data).
- **Admin UI**: A dedicated interface to view, filter, and inspect audit logs.
- **Immutable History**: Designed to be a permanent record of system activity.

## Architecture

### Core Components
- **`AuditService`**: The main service class responsible for sanitizing data and writing to the `cms_audit_logs` table.
- **`AuditController`**: Provides the API endpoints for the Admin UI (`/@/audit-trail`).
- **`AuditLog.js`**: The Vue.js component that renders the audit log table and details view.

### Database Schema
The plugin creates a `cms_audit_logs` table:
- `user_id`: ID of the authenticated user (or NULL for system actions).
- `action`: The type of action (e.g., `create`, `update`, `delete`).
- `resource_type`: The entity being modified (e.g., `page`, `user`).
- `resource_id`: The ID of the entity.
- `payload`: Sanitized JSON of the request data.
- `old_value/new_value`: Snapshots of data changes (where supported).
- `ip_address` & `user_agent`: Request metadata.

## Integration Hooks

The plugin uses the following Core Framework hooks to ensure comprehensive coverage:

### Database Hooks (`GaiaAlpha\Model\DB`)
- **`db_create_after`**: Triggered after a new record is inserted. Logs the `create` action.
- **`db_update_before`**: Triggered before a record is updated. Logs the `update` action.
- **`db_delete_before`**: Triggered before a record is deleted. Logs the `delete` action.

### Router Hooks
- **`router_matched`**: Used to attempt to guess the resource context from the URL structure (e.g., extracting `user` and `1` from `/api/users/1`).

## Configuration

The plugin works out-of-the-box. Ensure it is listed in `my-data/active_plugins.json`.

```json
[
    "AuditTrail"
]
```

## Security

- **Access Control**: The Audit Log UI and API are restricted to users with the `super_admin` role (Level 100).
- **Sanitization**: Passwords, tokens, and other sensitive fields defined in `AuditService::SENSITIVE_FIELDS` are automatically redacted from the logs.
