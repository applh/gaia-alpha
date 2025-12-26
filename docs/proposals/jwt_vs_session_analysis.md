# JWT vs. Session Analysis: Integration Report

## 1. Executive Summary

This report evaluates the integration of JSON Web Tokens (JWT) into Gaia Alpha CMS alongside the existing PHP Session mechanism.

**Recommendation**: Adopt a **Hybrid Authentication Model**.
- **Browser / Admin Panel**: Continue using **PHP Sessions**. It remains the most secure (HttpOnly cookies) and robust method for stateful browser interactions, offering out-of-the-box protection against XSS and simple revocation.
- **API / Mobile / Headless**: Implement **JWT** as the standard authentication method. It solves the "cookie jar" complexity in automated testing, enables easy scaling for stateless clients, and decouples the API from server-side storage I/O.

## 2. The Testing Argument

**Hypothesis**: *Does implementing JWT simplify automated testing of APIs and front-end features?*

**Verdict**: **YES**.

Integrating JWTs significantly reduces the complexity of automated testing (CI/CD) and local development for API endpoints.

### Evidence
1.  **Stateless Request Flows**: 
    - *With Sessions*: Tests must define a "cookie jar," perform a login POST request, capture the `PHPSESSID`, and inject it into every subsequent curl/request.
    - *With JWT*: Tests generate a token *once* (programmatically) in the `setUp()` phase and inject it into the `Authorization` header. No state or cookie maintenance is required.
    - *See*: `tests/VerifyJwtAuth.php` demonstrates how a token can be generated instantly without hitting a database or file system, making tests faster and less brittle.
2.  **Concurrency**:
    - Session files lock the session file during execution, preventing concurrent requests from the same "user" (test runner) from executing in parallel unless carefully closed.
    - JWTs are CPU-bound (crypto verification) and strictly non-blocking, allowing parallel test execution against the API.

## 3. Comprehensive Comparison

| Feature | PHP Session (Current) | JWT (Proposed Plugin) |
| :--- | :--- | :--- |
| **State Storage** | **Server-Side**<br>(File system `/tmp` by default, or Redis) | **Client-Side**<br>(Token contains all data) |
| **Transport** | `Cookie: PHPSESSID` (HttpOnly, Secure) | `Authorization: Bearer <token>` |
| **Scalability** | **Difficult**: Requires sticky sessions or shared storage (Redis) for multi-server. | **Easy**: Truly stateless; any server with the secret key can verify the request. |
| **Testing** | **Complex**: Requires mock browser/cookie management. | **Simple**: Just set a header. |
| **Performance** | **I/O Bound**: File read/lock/write per request. | **CPU Bound**: HMAC signature verification. |
| **Payload** | **Tiny**: ~32 bytes (Session ID). | **Large**: ~300-500 bytes (Base64 headers/claims). |
| **Security Risk** | **CSRF**: Prone to CSRF (needs tokens).<br>**XSS**: Safe (HttpOnly). | **XSS**: Vulnerable if stored in LocalStorage.<br>**CSRF**: Immune (headers are not auto-sent). |
| **Revocation** | **Instant**: Server deletes the session file. | **Hard**: Requires "blacklisting" (database state) or short TTLs. |

## 4. Architecture & Co-existence

JWT should be implemented as a **Core-Supported Plugin** (`plugins/JwtAuth`) rather than a mandatory replacement.

### The Hybrid Middleware Strategy
To support both seamlessly, we introduce a `JwtAuthMiddleware` that runs early in the request lifecycle (Hook: `auth_verify_request`).

1.  **Detection**: The middleware checks for the `Authorization: Bearer` header.
2.  **Validation**:
    - **If Present**: It validates the signature.
        - *Success*: Sets the application `Context::user` and bypasses `Session::start()`.
        - *Failure*: Returns 401 Unauthorized immediately.
    - **If Absent**: The request proceeds to the default logic, which calls `Session::start()` for browser clients.
3.  **Result**:
    - Admin Panel users (Browsers) get `Cookie` sessions.
    - API clients (cURL, Mobile, Test Runners) get `JWT` statelessness.

### Use Cases
-   **Admin Panel**: Use **Sessions**. Admins need high security (HttpOnly) and instant logout capabilities.
-   **API**: Use **JWTs**. Perfect for third-party integrations, mobile apps, and automated testing suites.

## 5. Performance Analysis

### CPU vs I/O Trade-off
-   **PHP Sessions (I/O)**: Every request triggers a `flock()` (file lock) on the session file. This serializes requests from the same user. If an API client fires 10 simultaneous requests, they will execute *sequentially* (1...10).
-   **JWT (CPU)**: Every request requires `hash_hmac('sha256', ...)` verification. This takes microseconds. 10 simultaneous requests verify in *parallel*.

### Network Payload
-   **Session**: Adds negligible overhead per request.
-   **JWT**: Adds ~500 bytes per request (Input Header) and potentially in the response if rotating tokens. for high-volume, low-bandwidth mobile scenarios, this is a minor factory, but for standard broadband/server comms, it is negligible.

### Conclusion
For high-concurrency API usage, **JWT is vastly superior performance-wise** because it eliminates the file-locking bottleneck of PHP sessions.

## 6. Next Steps for Implementation
1.  Finalize `plugins/JwtAuth` with the Middleware logic described above.
2.  Update `tests/bootstrap.php` or `ContextTest.php` to include JWT helpers.
3.  Document the "Dual Auth" pattern for plugin developers (i.e., "Check `Context::user()`, don't rely on `$_SESSION` directly").
