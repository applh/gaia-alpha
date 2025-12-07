# Security Architecture: PHP Sessions vs. JWT

This document outlines the architectural decision to use **PHP Sessions** over **JSON Web Tokens (JWT)** for authentication in *Gaia Alpha*, highlighting the security implications and trade-offs.

## 1. Overview

*   **PHP Sessions**: Traditional, server-side stateful authentication. The server creates a session file (or DB entry) and sends a Session ID (`PHPSESSID`) to the client via an `HttpOnly` cookie.
*   **JWT (JSON Web Tokens)**: Modern, client-side stateless authentication. The server signs a JSON payload, helping the client store state.

## 2. PHP Sessions
*Gaia Alpha uses generic PHP sessions for simplicity and security.*

### Implementation
- **Storage**: Server-side (default: file system `session_save_path`, typically `/tmp` or `/var/lib/php/sessions`).
- **Transport**: `Cookie` header (`PHPSESSID`).
- **Security**:
  - `HttpOnly`: Prevents JavaScript access to the cookie (Mitigates XSS).
  - `SameSite`: Strict/Lax enforcement prevents CSRF in modern browsers.
  - `Secure`: Cookie only sent over HTTPS.

### Pros
- **Revocation**: Admins can instantly kill a user's session (delete the file), logging them out everywhere.
- **Small Payload**: Only a small ID string is sent over the network.
- **Security**: Mature, battle-tested defaults in PHP.
- **Complexity**: Zero code required for token management; handled by PHP core.

### Cons
- **Scalability**: Stateful. Requires "sticky sessions" or a central store (Redis) if scaling to multiple load-balanced servers.
- **CSRF**: Vulnerable to Cross-Site Request Forgery if `SameSite` is not set or older browsers are supported (requires CSRF tokens).

## 3. JSON Web Tokens (JWT)

### Implementation
- **Storage**: Client-side (LocalStorage or Cookie).
- **Transport**: `Authorization: Bearer <token>` header.
- **Security**: Cryptographically signed payload.

### Pros
- **Stateless**: Server doesn't need to look up session data; verification is CPU-bound only.
- **Scalability**: Works seamlessly across multiple servers/microservices without shared storage.
- **Mobile**: Easier to handle in non-browser clients (Native Apps).

### Cons
- **Storage Risk (XSS)**: Storing JWT in `LocalStorage` allows any XSS script to steal the token and impersonate the user.
- **No Revocation**: A valid JWT cannot be banned until it expires. You must implement "blacklists" (Redis), effectively making it stateful again.
- **Payload Size**: Tokens can get large, increasing bandwidth on every request.

## 4. Security Showdown

| Feature | PHP Sessions (Cookies) | JWT (LocalStorage) |
| :--- | :--- | :--- |
| **XSS Resistance** | **High** (HttpOnly cookies are invisible to JS) | **Low** (Accessible by JS, easily stolen) |
| **CSRF Resistance** | **Medium** (Needs CSRF Tokens/SameSite) | **High** (Auth header is not auto-sent) |
| **Revocation** | **Instant** (Server-side deletion) | **Impossible** (Until expiration) |
| **Complexity** | **Low** (Native PHP) | **High** (Key management, signing) |

## 5. Decision for Gaia Alpha

We chose **PHP Sessions** because:
1.  **Monolithic Architecture**: Gaia Alpha is a single-server application; scaling state is not a concern.
2.  **Security First**: Leveraging `HttpOnly` cookies protects our users from token theft via XSS, which is a common vulnerability in modern SPAs.
3.  **Simplicity**: PHP's native session handling allows us to focus on features rather than implementing complex token rotation and blacklisting logic.
