<?php

namespace AuditTrail;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Request;
use GaiaAlpha\Session;

class AuditService
{
    private static $context = [
        'resource_type' => null,
        'resource_id' => null,
        'action' => null,
        'old_value' => null,
    ];

    /**
     * Set the current audit context
     */
    public static function setContext(string $key, $value)
    {
        self::$context[$key] = $value;
    }

    /**
     * Get the current audit context
     */
    public static function getContext(?string $key = null)
    {
        if ($key === null) {
            return self::$context;
        }
        return self::$context[$key] ?? null;
    }

    /**
     * Log an action to the database
     */
    public static function log(string $action, $resourceType = null, $resourceId = null, $oldValue = null, $newValue = null)
    {
        $payload = Request::input();
        $payload = self::sanitize($payload);

        // SQL params need to match placeholders
        // My previous SQL used named params with $db->query($sql, $data)
        // DB::query uses execute($params). If I use named placeholders, $data must be assoc array.
        // If I use ?, $data must be indexed array.
        // DB::query passes params to execute(). PDO supports named params if keys match.
        // Let's stick to assoc array but verify DB::query supports it.
        // DB::query calls $db->prepare($sql); $stmt->execute($params);
        // It should support key-value if $sql uses :key.

        $data = [
            'user_id' => Session::id(),
            'action' => $action,
            'method' => Request::server('REQUEST_METHOD', 'UNKNOWN'),
            'endpoint' => Request::uri(),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'payload' => json_encode($payload),
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent()
        ];

        $sql = "INSERT INTO cms_audit_logs 
                (user_id, action, method, endpoint, resource_type, resource_id, payload, old_value, new_value, ip_address, user_agent) 
                VALUES 
                (:user_id, :action, :method, :endpoint, :resource_type, :resource_id, :payload, :old_value, :new_value, :ip_address, :user_agent)";

        try {
            DB::query($sql, $data);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the app? 
            // Or log to file? For now, let's just log to error_log
            error_log("AuditTrail Error: " . $e->getMessage());
        }
    }

    /**
     * Sanitize sensitive data from payload
     */
    private static function sanitize($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive = ['password', 'token', 'secret', 'key', 'auth', 'card', 'cvv'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } else {
                foreach ($sensitive as $s) {
                    if (strpos(strtolower($key), $s) !== false) {
                        $data[$key] = '***REDACTED***';
                        break;
                    }
                }
            }
        }

        return $data;
    }
}
