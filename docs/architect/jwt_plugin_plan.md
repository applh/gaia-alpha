# Implementation Plan: JWT Management Plugin

This document outlines the design and implementation plan for a `JwtAuth` plugin in Gaia Alpha CMS.

## Goal
Provide a core-compatible plugin that enables JWT generation, validation, and integration with the existing authentication flow.

## Architecture

### 1. Plugin Structure
The plugin will follow the standard Gaia Alpha structure:
- `plugins/JwtAuth/index.php`: Initialization and hook registration.
- `plugins/JwtAuth/plugin.json`: Metadata.
- `plugins/JwtAuth/class/Service.php`: Core logic for token handling.
- `plugins/JwtAuth/class/Middleware.php`: Handles request interception.
- `plugins/JwtAuth/view/settings.php`: Admin UI for configuration.

### 2. Core Features
- **Library Integration**:
    - *Option A: Third-Party Library (e.g., `firebase/php-jwt`)*
        - **Pros**: Security-audited, community-maintained, handles complex algorithms (RS256) and edge cases effectively.
        - **Cons**: Adds external dependencies, requires Composer, slightly larger footprint.
    - *Option B: Custom Implementation*
        - **Pros**: Zero dependencies, extremely lightweight, direct control over implementation.
        - **Cons**: Higher security risk (e.g., implementation flaws), maintenance burden, manually handling crypto-specific requirements.
    - **Recommendation**: Use a well-established library like `firebase/php-jwt` for production environments to ensure cryptographic robustness.
- **Key Management**:
    - Support for Symmetric (HS256) and Asymmetric (RS256) algorithms.
    - Secure storage of secrets in `my-config.php` or a dedicated settings file.
- **Authentication Hooks**:
    - Hook into `auth_verify_request` to support Bearer tokens alongside sessions.
    - Hook into `user_login_success` to optionally return a JWT.
- **Claims Mapping**:
    - Allow mapping of CMS user roles to JWT claims.
    - Support site-specific tokens (multisite awareness).

## Proposed Implementation Steps

### Phase 1: Core Service
- [ ] Implement `JwtAuth\Service` class.
- [ ] Add methods for `generateToken(User $user)` and `validateToken(string $token)`.
- [ ] Implement robust error handling for expired or malformed tokens.

### Phase 2: Request Interception
- [ ] Create `JwtAuth\Middleware`.
- [ ] Register a hook that runs early in the request lifecycle.
- [ ] If an `Authorization: Bearer` header is present, validate it and set the current user context.

### Phase 3: Admin UI
- [ ] Create a settings page in the CMS dashboard.
- [ ] Allow administrators to refresh the secret key.
- [ ] Configure default expiration times (TTL).

### Phase 4: Developer Tools
- [ ] Add a CLI command `jwt:generate --user=<id>` for testing.
- [ ] Add a CLI command `jwt:verify <token>` for debugging.

## Security Considerations
- **Token Invalidation**: Implement a "JTI" (JWT ID) blacklist in the database to allow revoking specific tokens before they expire.
- **Storage**: Tokens should never be stored in the database regularly; only their JTI if blacklisting is enabled.
- **Transport**: Ensure the documentation emphasizes that JWT authentication *requires* HTTPS.

## Future Enhancements
- Support for Refresh Tokens.
- Integration with external Identity Providers (OIDC).
