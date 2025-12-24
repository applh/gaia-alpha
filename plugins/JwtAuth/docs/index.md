# JwtAuth Plugin

## Objective
The **JwtAuth** plugin provides **JSON Web Token (JWT)** management for authentication and stateless APIs. It allows administrators to configure JWT settings (TTL, Secret) and handles token verification via middleware.

## Configuration
- **Type**: `core`
- **Menu**: Adds "JWT Settings" to the "System" group.

## Hooks
- **`app_boot`**: Registers middleware to handle JWT authentication early in the request lifecycle.
- **`framework_load_controllers_after`**: Registers `JwtSettingsController`.
- **`cli_resolve_command`**: Registers JWT-related CLI commands.

## UI Components
- **JwtSettings**: A Vue.js component for managing JWT configuration in the admin panel.
