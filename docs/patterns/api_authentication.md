# API Authentication Pattern

Gaia Alpha uses a **Hybrid Authentication Model** to provide the best of both worlds: secure, easy state management for browsers (Sessions) and stateless, scalable access for automated tools and third-party apps (JWT).

## Overview

- **Browser / Admin Panel**: Uses **PHP Sessions** (`PHPSESSID` cookie).
- **API / Mobile / Automation**: Uses **JSON Web Tokens (JWT)** (`Authorization: Bearer <token>`).

## Implementation Features

### 1. Hybrid Middleware
The authentication logic is handled by middleware that inspects the request *before* it reaches your controller.
- It checks for the `Authorization: Bearer` header.
- If found and valid, it sets the user context without starting a PHP session.
- If missing, it falls back to standard Session handling.

### 2. Authorization Header
API clients should include the token in the standard header:
`Authorization: Bearer <your-jwt-token>`

### 3. Controller Protection
Controllers remain agnostic to *how* the user authenticated. You simply check:

```php
// In your Controller
public function index()
{
    // Verification works for BOTH Session and JWT users
    if (!$this->requireAuth()) return;
    
    // Access user data universally
    $userId = Session::id(); // Maps to JWT 'sub' or Session 'user_id'
}
```

*Note: The `Session` class helper is patched/wrapped to return data from the current Request Context, whether it came from `$_SESSION` or a decoded JWT.*

## Best Practices

### For API Clients
- **Obtain Token**: Call `POST /@/api/login` with username/password to receive a token.
- **Store Securely**: Do not store in `localStorage` if XSS is a concern. For pure M2M or Test runners, memory storage is fine.
- **Handle 401**: If the token expires, the API returns `401 Unauthorized`. implementing a refresh flow or re-login is required.

### For Plugin Developers
- **Do not reply on `$_SESSION` directly**: Use `Session::get('key')` or `Context::user()` methods. Direct access to the `$_SESSION` superglobal will fail for JWT users because `session_start()` is never called.
- **CSRF**: JWT requests are immune to CSRF because the browser does not automatically send the custom `Authorization` header. You can skip CSRF checks if the request is authenticated via JWT.

## Testing
See [Context-Aware Testing](../testing/context_aware_testing.md) for details on how to easily mock authenticated requests using JWTs.
