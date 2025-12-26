# Patterns: Hooks & Performance

Gaia Alpha uses a synchronous `Hook` system (`GaiaAlpha\Hook`) to allow plugins to extend core functionality without modifying core files. While powerful, misusing hooks can degrade application performance.

## 1. When to Add Hooks
Hooks should be added when you anticipate that plugins will need to:
- **Observe** an event (e.g., `audit_log`, `analytics`).
- **Modify** data before it is saved or displayed (e.g., `filter_content`).
- **Interrupt** a process (e.g., `auth_check`).

### Common Locations
- **Controller Actions**: Before/After executing a route (`router_dispatch_before`).
- **Data Persistence**: Before/After saving to the database (`db_create_after`).
- **Response Rendering**: modifying output before flush (`response_send_before`).

## 2. Naming Conventions
Use snake_case.
- **Timing Suffixes**: `_before`, `_after` (e.g., `user_login_after`).
- **Action Verbs**: `verb_noun` or `noun_verb` (e.g., `router_matched`).

## 3. Implementation
To add a hook in the core (or your own plugin), simply call `Hook::run`.

```php
use GaiaAlpha\Hook;

public function save($data)
{
    // 1. Allow plugins to modify data
    $data = Hook::filter('my_data_save_filter', $data);

    // 2. Notify plugins before action
    Hook::run('my_action_before', $data);

    // ... perform save ...

    // 3. Notify plugins after action
    Hook::run('my_action_after', $result);
}
```

## 4. Performance Assessment
**CRITICAL**: Hooks in Gaia Alpha execute **synchronously**. If a hook takes 1 second to run, the user waits 1 extra second.

### Performance Rules
1.  **No External HTTP Calls**: Never make an API call (e.g., to Slack, Stripe, or an AI service) inside a main thread hook. Use a background job or queue instead.
2.  **Lightweight Logic**: Keep listeners fast. 
3.  **Database Queries**: A single SQL query is usually fine. N+1 queries in a loop are not.
4.  **File I/O**: Avoid heavy file operations. Use cached manifests for discovery logic.
5.  **Context Alignment**: Ensure hooks only run where they are needed.

### Contextual Hook Filtering
Gaia Alpha supports request context filtering (`public`, `admin`, `api`). Use this to prevent unnecessary code execution.

```php
// Only run this hook on public frontend pages
Hook::add('router_dispatch_after', [$this, 'trackVisit'], 10, 'public');

// Only run this hook in the admin panel
Hook::add('auth_session_data', [$this, 'injectMenu'], 10, 'admin');
```

This ensures that "heavy" admin listeners don't slow down public page delivery.

### Measuring Impact

## 5. Case Study: Database Hooks
We recently added hooks to `GaiaAlpha\Model\DB` to support the [Audit Trail plugin](../plugins/audit_trail.md).

**Goal**: Log every data change.
**Risk**: Adding overhead to every `INSERT`, `UPDATE`, and `DELETE`.
**Mitigation**:
- The `AuditTrail` listener performs a single `INSERT` into `cms_audit_logs`.
- It does **not** perform complex logic or external calls.
- Estimated overhead: < 5ms per write. This is acceptable for a CMS.

If the Audit Trail were to *send an email* on every update, that would violate our performance rules and should be moved to a background process (e.g., `cron`).
