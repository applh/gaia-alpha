# Admin Component Builder - DevOps Guide

## Deployment Requirements

The Admin Component Builder is part of the core monolithic application but has specific file system requirements.

### File System Permissions
The application needs **Write Access** to:
- `resources/js/components/custom/`: This is where generated `.js` component files are saved.
- Ensure the web server user (e.g., `www-data`) has `+w` permission on this directory.
- Failure to write will prevent new components from being created, though existing ones may still load if cached.

### Database
- **Migrations**: New tables `admin_components`, `admin_component_versions`, `admin_component_permissions` must be migrated.
- **Seeding**: Initial seed data is available for testing/demo purposes (`php gaia data:seed`).

### Caching
- **Browser Cache**: Generated components are static JS files. Ensure your Nginx/Apache config handles browser caching correctly for `.js` files in `resources/js/`.
- **Versioning**: The file names or query strings should ideally use the `updated_at` timestamp to bust cache when a user modifies a component (handled by the frontend loader).

### Security
- **Access Control**: The builder APIs `/@/admin/component-builder/*` should be strictly restricted to Admin users via middleware (`requireAdmin`).
- **Sanitization**: The code generator runs server-side. While it generates JS, it uses strict templating. Ensure no Raw HTML injection is allowed in the `label` or `props` fields unless intended.

## Troubleshooting
- **404 on Custom Component**: 
  - Check if the file exists in `resources/js/components/custom/`.
  - Check if the database record exists in `admin_components`.
  - Verify case sensitivity (Linux vs Mac).
- **"Chart is not defined"**: 
  - Ensure `resources/js/vendor/chart.js` is present (Action: `curl` download required in setup).
