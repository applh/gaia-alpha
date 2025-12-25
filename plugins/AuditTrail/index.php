<?php

use AuditTrail\AuditService;
use GaiaAlpha\Hook;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use AuditTrail\AuditController;

// 1. Initialize Audit Context on Route Match
Hook::add('router_matched', function ($route, $matches) {
    // Attempt to guess resource type from path
    // e.g. /api/users/1 -> resource_type=user, resource_id=1
    $path = $route['path']; // e.g., /api/users/(\d+)

    // Simple heuristic for auto-context
    if (preg_match('#/api/([a-z]+)/(\d+)#', $path, $m)) {
        AuditService::setContext('resource_type', rtrim($m[1], 's')); // simple singularization
        AuditService::setContext('resource_id', $m[2]);
    }
}, 10);

// 2. Hook into Database Actions for Automatic Logging

// Create
Hook::add('db_create_after', function ($table, $id, $data) {
    // Map table to resource type (e.g., cms_pages -> page)
    $resourceType = str_replace('cms_', '', $table);
    $resourceType = rtrim($resourceType, 's');

    if ($table === 'cms_audit_logs')
        return; // Prevent infinite loop

    AuditService::log('create', $resourceType, $id, null, $data);
});

// Update
Hook::add('db_update_before', function ($table, $id, $data) {
    // Map table to resource
    $resourceType = str_replace('cms_', '', $table);
    $resourceType = rtrim($resourceType, 's');

    if ($table === 'cms_audit_logs')
        return;

    // Fetch old value?
    // DB::find($id) might trigger another query hook? 
    // DB query wrapper triggers 'database_query_executed', not 'db_update_before'.
    // So usually safe. But let's check class usage to be sure we don't recurse.

    // For now, let's just log the update action with the new data.
    // Fetching old data synchronously might be expensive.

    AuditService::log('update', $resourceType, $id, null, $data);
});

// Delete
Hook::add('db_delete_before', function ($table, $id) {
    $resourceType = str_replace('cms_', '', $table);
    $resourceType = rtrim($resourceType, 's');

    if ($table === 'cms_audit_logs')
        return;

    AuditService::log('delete', $resourceType, $id);
});


// 3. Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('audit_trail', AuditController::class);
});


// 3. Log 404s on sensitive paths?
// Hook::add('router_404', function($uri) {
//     // Implementation for logging failed access attempts
// });
