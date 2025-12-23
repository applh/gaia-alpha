# JWT Use Cases in Gaia Alpha CMS

JSON Web Tokens (JWT) provide a secure, stateless way to transmit information between parties as a JSON object. In the context of Gaia Alpha CMS, JWTs are particularly useful for decoupling authentication from session state and enabling multi-site or third-party integrations.

## 1. Stateless API Authentication
The primary use case for JWT is securing REST or GraphQL APIs.
- **Scenario**: A mobile app or a Vue/React frontend needs to interact with the CMS.
- **How it works**: The client authenticates once (e.g., username/password), and the CMS returns a JWT. The client sends this token in the `Authorization: Bearer <token>` header for subsequent requests.
- **Benefit**: No need for PHP sessions or cookies, making it easier to scale and handle cross-origin requests.

## 2. Multi-Site Single Sign-On (SSO)
Gaia Alpha supports multiple sites via `my-data/sites`. JWT can facilitate seamless navigation between these sites.
- **Scenario**: A user is logged into `site-a.com` and wants to visit `site-b.com` (both managed by the same CMS instance) without re-logging in.
- **How it works**: `site-a.com` generates a short-lived, signed JWT containing the user's identity and redirects to `site-b.com/sso?token=...`. `site-b.com` validates the token and logs the user in.
- **Benefit**: Unified user experience across a multisite network.

## 3. Secure File Downloads & Private Assets
Protecting premium or sensitive content.
- **Scenario**: A user purchases a digital download.
- **How it works**: The CMS generates a JWT that authorizes access to a specific file for a limited time (e.g., 5 minutes). The download link includes this token.
- **Benefit**: Prevents "hotlinking" and ensures only authorized users can access the file without exposing permanent file paths.

## 4. Password Reset & Email Verification
Replacing traditional opaque tokens in the database.
- **Scenario**: A user requests a password reset link.
- **How it works**: The CMS sends an email with a JWT containing the user's ID and an expiration timestamp (`exp`). When the user clicks the link, the CMS verifies the signature and expiration without needing to look up a token in a `password_resets` table.
- **Benefit**: Reduces database load and cleanup logic for expired tokens.

## 5. Third-Party Webhooks & Callbacks
Verifying that an incoming request is actually from a trusted source.
- **Scenario**: An external service (e.g., a payment gateway) notifies the CMS of a successful transaction.
- **How it works**: The external service (if it supports JWT) sends a token signed with a shared secret.
- **Benefit**: Cryptographic assurance that the notification hasn't been tampered with.

## 6. Microservices / Component Communication
If Gaia Alpha is part of a larger architecture.
- **Scenario**: A separate search service or image processing service needs to know which user is making a request.
- **How it works**: Gaia Alpha passes the JWT to the downstream service, which can trust the user identity without calling back to Gaia Alpha.
- **Benefit**: Reduced latency and decoupled architectural components.
