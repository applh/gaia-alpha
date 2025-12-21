# Cybersecurity Plugin

## Objective
The **Cybersecurity** plugin is designed to enhance the security posture of a Gaia Alpha installation. It focuses on proactive threat detection, audit logging, and the automated application of security best practices.

## Configuration
- **Type**: `core` (Recommended)
- **Menu**: Adds "Security Center" to the "System" group.
- **DataStore**: Uses `security_config` for IP blacklists and rate-limiting thresholds.

## Architectural Overview
The plugin operates primarily through middleware registered during the `app_boot` hook to intercept requests before they reach the controller layer.

### Feature Modules

#### 1. Firewall & Rate Limiting
Intercepts incoming requests to check against a blacklist of IPs stored in the `DataStore`. It also tracks request frequency to mitigate brute-force and DDoS attempts.

#### 2. Audit Logger
Monitors sensitive actions (login attempts, page deletions, plugin installations) and logs them for administrative review.

#### 3. Security Headers
Automatically injects recommended HTTP security headers:
- `Content-Security-Policy` (CSP)
- `Strict-Transport-Security` (HSTS)
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`

## Hooks
- **`app_boot`**: Registers the `FirewallMiddleware` and `SecurityHeadersMiddleware`.
- **`auth_login_success`**: Logs successful authentication events.
- **`auth_login_failure`**: Logs failed attempts and triggers rate-limiting logic.
- **`framework_load_controllers_after`**: Registers the `SecurityDashboardController`.

## Implementation Guide

### Service Pattern: ThreatDetector
Use a dedicated Service to handle threat logic.

```php
namespace Cybersecurity\Service;

use GaiaAlpha\DataStore;

class ThreatDetector {
    public static function isIpBlocked(string $ip): bool {
        $blacklist = DataStore::get('security_blacklist', []);
        return in_array($ip, $blacklist);
    }
    
    public static function logEvent(string $type, array $data) {
        // Implementation for audit logging
    }
}
```

### Hook Registration in `index.php`

```php
use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Cybersecurity\Service\ThreatDetector;

Hook::add('app_boot', function() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (ThreatDetector::isIpBlocked($ip)) {
        http_response_code(403);
        die("Access Denied.");
    }
});
```

## UI Components
- **SecurityDashboard**: A Vue.js component providing a real-time view of blocked attempts, active threats, and audit logs.

## CLI Commands
- `php gaia security:blacklist <ip>`: Manually add an IP to the blacklist.
- `php gaia security:audit:tail`: Stream recent security logs to the console.
