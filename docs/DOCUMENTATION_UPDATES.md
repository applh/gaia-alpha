# Documentation Updates Summary

This document summarizes the improvements made to prevent common development errors.

## New Documentation

### `/docs/patterns/common_pitfalls.md`
Comprehensive guide covering:
- ✅ Database access (DB vs DataStore)
- ✅ Authentication patterns (requireAuth return values)
- ✅ Asset serving (plugin paths)
- ✅ Chart.js / external libraries
- ✅ Database table creation
- ✅ Import paths in Vue components
- ✅ Response handling
- ✅ Session access
- ✅ JSON parsing
- ✅ Quick reference tables
- ✅ Common error messages lookup

## Updated Documentation

### `/docs/patterns/controller.md`
**Changes:**
- ✅ Added proper `use` statements (Request, Response, Session)
- ✅ Fixed authentication pattern: `if (!$this->requireAuth()) return;`
- ✅ Added `return` after error responses
- ✅ Showed proper Session::id() usage
- ✅ Added comments highlighting critical patterns

### `/docs/patterns/service.md`
**Changes:**
- ✅ Replaced example with database-focused service
- ✅ Showed correct `use GaiaAlpha\Model\DB;` import
- ✅ Demonstrated all DB methods (fetch, fetchAll, execute, lastInsertId)
- ✅ Added "Database Access Best Practices" section
- ✅ Added "Never use" warnings
- ✅ Updated checklist with DB usage requirement

## Key Patterns to Remember

### 1. Database Access
```php
use GaiaAlpha\Model\DB;  // ✅ Correct

// NOT DataStore::getDb()  ❌ Wrong
```

### 2. Authentication
```php
if (!$this->requireAuth()) return;  // ✅ Must check and exit
```

### 3. Response Handling
```php
Response::json(['error' => 'Message'], 400);
return;  // ✅ Must exit after response
```

### 4. Chart.js
```javascript
// Import from CDN and register controllers
const ChartModule = await import('https://cdn.jsdelivr.net/npm/chart.js@4.4.7/+esm');
const { Chart, LineController, ... } = ChartModule;
Chart.register(...);  // ✅ Required
```

## Files Modified

1. `/docs/patterns/common_pitfalls.md` - **NEW**
2. `/docs/patterns/controller.md` - **UPDATED**
3. `/docs/patterns/service.md` - **UPDATED**

## Impact

These documentation updates will help prevent:
- ❌ `Class "GaiaAlpha\DataStore" not found` errors
- ❌ `Argument must be of type int, null given` errors
- ❌ `404 Not Found` for plugin assets
- ❌ `Failed to resolve module specifier` errors
- ❌ `Controller not registered` errors
- ❌ Multiple response sending issues


## Frontend Architecture Updates (2025-12-24)

### New Standard Components
1.  **TreeView (`ui/TreeView.js`)**: Generic, recursive tree component with advanced drag-and-drop (reordering & nesting).
2.  **AsyncForm (`ui/AsyncForm.js`)**: Standardized form wrapper handling loading states, success/error feedback, and accessible errors.

### Component Updates
1.  **Refactored `FileExplorer.js`, `TodoList.js`, `ComponentTree.js`** to use shared `TreeView`.
2.  **Refactored `SiteSettings.js`, `UsersAdmin.js`** to use shared `AsyncForm` (Enabling "Premium" UX feedback everywhere).

### Key Frontend Patterns
-   **Composition**: Use generic UI primitives (`ui/`) instead of duplicating logic.
-   **Data-Driven**: Transform flat data to trees in computed properties (e.g. `TodoList`'s `treeData`) rather than managing nested state manually.
-   **Async UX**: Always provide visual feedback for async actions (now automatic with `AsyncForm`).

### Additional Refactoring (Login & CMS)
-   **`Login.js`**: Standardized login form with `AsyncForm`.
-   **`CMS.js`**: Replaced manual page/template form handling with `AsyncForm` for unified UX.

## Architectural & Performance Upgrades (2025-12-26)

### New Patterns & Contexts
1. **[Application Context System](patterns/context.md)**: Detailed documentation of the refined context system (`api`, `app`, `admin`, etc.) with configurable prefixes.
2. **[API Standardization Pattern](patterns/api_standardization.md)**: Standardized usage of the new `Api.js` helper for consistent, secure, and performant frontend requests.

### Enhanced Testing Guides
1. **[Backend Testing Guide Refresh](testing/backend.md)**: Updated descriptions of the custom PHP test framework.
2. **[Context-Aware Testing Guide](testing/context_aware_testing.md)**: New guide on mocking contexts and server variables for robust backend verification.

### Core Documentation Refactor
1. **[System Architecture Update](core/architecture.md)**: Integrated context-based plugin loading and the new route structure into the core flow description.
2. **[Pattern Updates](patterns/controller.md)**: Refreshed controller and hook documentation to promote the `/@/api/` prefixing convention and fully qualified class references.

