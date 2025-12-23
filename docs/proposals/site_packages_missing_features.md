# Site Packages: Missing & Incomplete Features Assessment

## Executive Summary

This document identifies missing or incomplete features in the current Site Package import/export system. While the core functionality works well for basic content migration, several important features are not currently exported or imported, limiting the system's ability to create truly portable and complete site backups.

## Current Export Coverage

### ✅ Fully Implemented
- **Pages**: All CMS pages with front matter metadata
- **Templates**: Custom PHP templates from database
- **Forms**: Form definitions with schemas
- **Menus**: Navigation menus with structure
- **Custom Components**: React/JS components
- **Assets**: Public assets (CSS, JS, images)
- **Media**: Uploaded files referenced in content
- **Plugin Dependencies**: Listed in `site.json` manifest

### ⚠️ Partially Implemented
- **Site Configuration**: Only `theme` and `homepage` are imported (hardcoded in `WebsiteImporter::processConfig()`)
- **Active Plugins List**: Exported but only validated with a warning, not auto-activated

## Missing Features

### 1. Global Site Settings (High Priority)

**Current State**: Not exported or imported

**Impact**: Critical site configuration is lost during migration

**Missing Settings**:
- `site_title` - Site name/branding
- `site_description` - SEO meta description
- `site_keywords` - SEO keywords
- `site_language` - HTML lang attribute
- `favicon` - Site favicon path
- `logo` - Site logo path
- `robots_txt` - Custom robots.txt content
- Any other settings stored in `data_store` table with `type='global_config'`

**Location in Codebase**:
- Settings stored via `DataStore::set(0, 'global_config', $key, $value)`
- Retrieved via `DataStore::getAll(0, 'global_config')`
- Used in `ViewController`, `PublicController`, `InstallController`

**Evidence**:
```php
// From SettingsController.php
$settings = DataStore::getAll(0, 'global_config');
```

### 2. User Preferences (Medium Priority)

**Current State**: Not exported or imported

**Impact**: User-specific settings (theme, layout) are lost

**Missing Data**:
- User theme preferences (dark/light)
- Layout preferences (side/top)
- Any custom user settings stored in `data_store` with `type='user_pref'`

**Note**: While less critical than global settings, this affects user experience continuity

### 3. Plugin-Specific Configuration (Medium Priority)

**Current State**: Plugin list exported, but plugin-specific settings are not

**Impact**: Plugins may not function correctly after import without their configuration

**Examples**:
- JWT Auth settings (`jwt_settings` in `data_store`)
- API Builder configuration (`my-data/api-config.json`)
- Any plugin-specific `data_store` entries

**Challenge**: Plugin configurations are stored in various locations:
- `data_store` table (various types)
- JSON files in `my-data/`
- Plugin-specific directories

### 4. Database Schema Extensions (Low Priority)

**Current State**: Not exported or imported

**Impact**: Custom tables created by plugins are not migrated

**Consideration**: This is complex and may require plugin-specific export handlers

### 5. Page Versions/History (Low Priority)

**Current State**: Not exported or imported

**Impact**: Historical page versions are lost

**Note**: The `cms_page_versions` table exists but is not included in exports

### 6. Form Submissions (Low Priority)

**Current State**: Not exported or imported

**Impact**: Historical form submission data is lost

**Note**: This is likely intentional for privacy/GDPR reasons, but should be documented

### 7. User Accounts (Intentionally Excluded)

**Current State**: Not exported (documented as intentional)

**Impact**: User accounts must be recreated

**Rationale**: Security - passwords and user data should not be in portable packages

**Current Behavior**: A default `admin/admin` account is created on import

## Documentation Gaps

### 1. Incomplete Feature List

**Issue**: The documentation in `docs/user/site_packages_import_export.md` states:

> **User Accounts** are NOT exported for security reasons.
> **Global Settings** (like site title, debug mode) are currently not part of the export package.

The second point acknowledges the limitation but doesn't explain the impact or workarounds.

### 2. Missing Troubleshooting Guide

**Issue**: No guidance on:
- What to do when required plugins are missing
- How to manually migrate settings
- How to verify a successful import
- Common import/export errors

### 3. No Migration Checklist

**Issue**: Users don't have a pre/post-migration checklist to ensure completeness

## Technical Debt

### 1. Hardcoded Config Processing

**Location**: `WebsiteImporter::processConfig()`

```php
foreach ($config as $key => $val) {
    // Store in user_pref for now
    if ($key === 'theme' || $key === 'homepage') {
        DataStore::set($this->userId, 'user_pref', $key, $val);
    }
}
```

**Issue**: Only handles two specific config keys, ignoring all others

### 2. No Validation of Imported Data

**Issue**: The importer doesn't validate:
- Required fields in front matter
- Media file existence before rewriting paths
- Template syntax errors
- Form schema validity

### 3. No Rollback Mechanism

**Issue**: If import fails midway, there's no way to rollback to a clean state

### 4. Asset Path Assumptions

**Issue**: The exporter assumes assets are in `www/assets` or a provided path, but doesn't handle:
- Multi-site asset directories (`my-data/sites/{domain}/assets`)
- Absolute URLs to external assets
- Assets referenced in templates but not in the assets directory

## Recommendations

### Immediate Actions (High Priority)
1. **Export/Import Global Settings**: Add global site settings to the export package
2. **Document Limitations**: Update documentation to clearly list what is NOT exported
3. **Improve Error Handling**: Add validation and better error messages

### Short-term Improvements (Medium Priority)
1. **Plugin Configuration Export**: Create a standardized way for plugins to export their settings
2. **Import Verification Tool**: Enhance `site:verify-export` to check all expected data
3. **Migration Guide**: Create comprehensive migration documentation

### Long-term Enhancements (Low Priority)
1. **Incremental Imports**: Support updating existing sites without full replacement
2. **Selective Export**: Allow users to choose what to include in exports
3. **Plugin Export Hooks**: Allow plugins to register export/import handlers
4. **Database Schema Migration**: Support custom table exports

## Impact Analysis

### For Users
- **Site Cloning**: Cannot create exact replicas of sites
- **Staging/Production**: Settings must be manually reconfigured
- **Backups**: Exports are incomplete backups
- **Templates**: Cannot distribute fully-configured site templates

### For Developers
- **Testing**: Cannot easily create test environments from production
- **Plugin Development**: Plugin settings not preserved in test packages
- **CI/CD**: Automated deployments require manual configuration steps

## Conclusion

The current site package system provides a solid foundation for content migration but lacks critical features for complete site portability. The most significant gap is the absence of global site settings export/import, which should be prioritized for implementation.

The system would benefit from:
1. A comprehensive settings export/import mechanism
2. Better documentation of limitations
3. Validation and error handling improvements
4. A plugin-extensible architecture for custom data exports
