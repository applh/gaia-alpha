<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Response;
use GaiaAlpha\Request;

abstract class BaseController
{



    protected function requireAuth()
    {
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return false;
        }
        return true;
    }

    protected function requireAdmin()
    {
        if (!$this->requireAuth()) {
            return false;
        }
        if (!\GaiaAlpha\Session::isAdmin()) {
            Response::json(['error' => 'Forbidden'], 403);
            return false;
        }
        return true;
    }

    public function registerRoutes()
    {
        // Override in subclasses
    }

    public function init()
    {
        // Override in subclasses
    }

    public function getRank()
    {
        return 10;
    }

    /**
     * Helper to set Audit Logging Context
     */
    protected function setAuditContext(string $resourceType, $resourceId, ?array $oldValue = null)
    {
        if (class_exists('AuditTrail\\AuditService')) {
            \AuditTrail\AuditService::setContext('resource_type', $resourceType);
            \AuditTrail\AuditService::setContext('resource_id', $resourceId);
            if ($oldValue) {
                \AuditTrail\AuditService::setContext('old_value', $oldValue);
            }
        }
    }
}
