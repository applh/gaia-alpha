# Vue Components Documentation

Gaia Alpha uses a distributed, ES Module-based Vue 3 architecture. Each component is a standalone `.js` file loaded asynchronously.

## Core Layout (`site.js`)

The main entry point is `site.js`. It handles:
-   **Routing**: Simple state-based routing via `store.currentView`.
-   **Authentication**: Login/Register/Logout state management.
-   **Navigation**: Dynamic menu generation.

### Menu Structure
The navigation menu is data-driven and currently organized into the following groups:

-   **Dashboard**: Admin overview.
-   **Projects**: (Formerly "My Todos") Personal task management.
-   **Content**:
    -   *CMS*: Page management.
    -   *Templates*: Layout templates.
    -   *Forms*: Form builder and submissions.
    -   *Maps*: Geospatial data.
-   **System**:
    -   *Users*: User management.
    -   *Databases*: SQL query runner and schema viewer.
    -   *APIs*: Dynamic API builder.
    -   *Console*: Server-side webshell.

## Component Library

### `TodoList.js` (Projects)
The primary view for standard users.
-   **Features**: Drag-and-drop organization, nested tasks, calendar view, Gantt chart view.
-   **Renaming**: Labeled "Projects" in the UI to reflect its broader usage for task management.

### `AdminDashboard.js`
High-level statistics and quick links for administrators.

### `CMS.js`
Content Management System interface.
-   **Pages**: Create and edit markdown/HTML content.
-   **Templates**: Manage reusable layouts.

### `FormsAdmin.js`
Form builder and submission viewer.
-   **Builder**: Drag-and-drop form creation.
-   **Submissions**: View and export data collected from forms.

### `MapPanel.js`
Interactive mapping interface.
-   **2D/3D**: Switch between flat map and 3D globe.
-   **Layers**: Manage markers and geospatial data.

### `DatabaseManager.js`
Direct database administration.
-   **Query**: Run raw SQL.
-   **Schema**: Inspect tables and columns.
-   **Backup**: Export database (CLI-driven).

### `ApiManager.js`
No-code API endpoint creator.
-   **Endpoints**: Define SQL queries exposed as JSON APIs.
-   **Security**: Set access levels for endpoints.

### `UsersAdmin.js`
User lifecycle management.
-   **Roles**: Assign admin levels (0-100).
-   **Crud**: Create, update, delete users.
