# Audit Trail Plugin Proposal

## 1. Overview
The Audit Trail system will be implemented as a dedicated **Plugin** (`plugins/AuditTrail`). It provides immutable logging of administratie and API actions within the Gaia Alpha framework. This ensures accountability, security monitoring, and troubleshooting capabilities by tracking "who did what, when, and from where."

## 2. Goals
- **Immutability**: Logs cannot be modified or deleted by standard users.
- **Completeness**: Capture all state-changing actions (CREATE, UPDATE, DELETE).
- **Context**: Record the actor (user), action details, resulting state changes, and request metadata (IP, User Agent).
- **Pluggability**: Zero modification to core files; uses standard hooks.

## 3. Database Schema
The plugin will define its table in `plugins/AuditTrail/schema.sql`.

```sql
CREATE TABLE IF NOT EXISTS cms_audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,              -- User attempting the action (NULL if system/anon)
    action VARCHAR(64) NOT NULL,       -- e.g., 'page.create', 'user.login', 'api.update'
    method VARCHAR(10) NOT NULL,       -- GET, POST, PUT, DELETE
    endpoint VARCHAR(255) NOT NULL,    -- The URL/URI accessed
    resource_type VARCHAR(64) NULL,    -- e.g., 'Table', 'User', 'Page'
    resource_id VARCHAR(64) NULL,      -- ID of the affected resource
    payload TEXT NULL,                 -- JSON encoded request data (sanitized)
    old_value TEXT NULL,               -- JSON encoded previous state (for diffs)
    new_value TEXT NULL,               -- JSON encoded new state
    ip_address VARCHAR(45) NULL,       -- IPv4 or IPv6
    user_agent VARCHAR(255) NULL,      -- Browser/Client info
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_user ON cms_audit_logs(user_id);
CREATE INDEX idx_audit_action ON cms_audit_logs(action);
CREATE INDEX idx_audit_date ON cms_audit_logs(created_at);
```

## 4. Architecture

### 4.1 Namespace & Structure
Namespace: `AuditTrail`
Path: `plugins/AuditTrail/`

- `plugin.json`: Metadata and activation settings.
- `index.php`: Entry point, registers hooks.
- `class/AuditService.php`: Core logging logic.
- `class/AuditController.php`: Admin API for viewing logs.
- `resources/js/AuditLog.js`: Admin UI component.

### 4.2 Integration Hooks
The plugin will register listeners in `index.php` using `GaiaAlpha\Hook`.

**Automatic Capture Strategy**:
- **`router_matched`**: Initialize the audit context. Identify the route and potential resource.
- **`router_dispatch_after`**: If the request was successful (2xx status), commit the log.
- **`router_404`** / **Error**: Log "Attempted Access" or "Failure" for high-risk paths.

**Middleware Logic**:
For `POST`, `PUT`, `DELETE`, `PATCH` methods, logging is **mandatory**.

### 4.3 Data Sanitization
Before logging `payload`, sensitive fields (passwords, tokens, API keys) must be scrubbed/redacted.

## 5. Implementation Plan

### Phase 1: Core Plugin Setup
1. Create `plugins/AuditTrail` directory.
2. Create `plugin.json` and `schema.sql`.
3. Implement `AuditTrail\AuditService::log()`.
4. Register hooks in `plugins/AuditTrail/index.php`.

### Phase 2: Contextual Awareness
1. Enhance `Controller` base class (in core) to allow setting "Resource Context" (e.g., `$this->setAuditContext('page', $id)`).
2. Use `Hook::run('db_query_update')` (if available) or Model events.

### Phase 3: Admin UI
1. Create **Security > Audit Log** page in the Admin Dashboard via `AuditController`.
2. Features:
    - Data Table with lazy loading.
    - Filters: User, Date Range, Action Type.
    - Detail Modal: View JSON Diff of changes.

### Phase 4: Framework Hooks (Enhancement)
1. Add `Hook::run()` calls in `GaiaAlpha\Model\DB`:
   - `db_create_after`
   - `db_update_before`, `db_update_after`
   - `db_delete_before`
2. Update `AuditTrail` plugin to listen to these hooks instead of relying solely on `setAuditContext`.

## 6. Security Considerations
- **Access Control**: Only users with `super_admin` role can view audit logs.
- **Tamper Evidence**: Logs are stored in the database. Future versions could hash logs for integrity.

