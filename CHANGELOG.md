# Changelog

All notable changes to this project will be documented in this file.
## [v0.34.0] - 2025-12-15
### Added
- **Admin**: Async Component Builder. Visual drag-and-drop interface for creating custom admin panels without writing code.
- **Admin**: Component Library. Added "Stat Card" and "Data Table" components to the builder library.
- **Core**: `AdminComponentManager` and `ComponentCodeGenerator` services for generating Vue 3 async components from JSON definitions.
- **Database**: Added `admin_components`, `versions`, and `permissions` tables.

### Fixed
- **Builder**: Fixed "Unique constraint failed" error when saving existing components (implemented valid UPSERT logic).
- **Builder**: Fixed "Cannot read properties of null" error on Save button by using reactive state.
- **Builder**: Fixed Empty Tree View issue by flattening recursive VNode arrays.
- **Builder**: Fixed Layout Loading issue where nested `definition` data was not correctly hydrated on edit.
- **Builder**: Removed duplicate "Toolbox" header.

## [v0.33.0] - 2025-12-14
### Refactored
- **Database**: Replaced `LoggedPDO` with native `PDO` to fix signature mismatch warnings.
- **Database**: Centralized query logging via `BaseModel::query()`, `fetchAll()`, `fetch()`, `fetchColumn()`, and `execute()` helpers.
- **Coverage**: Refactored ALL Models (`Page`, `DataStore`, `Todo`, `Template`, `User`, `Message`, `MapMarker`, `Menu`) and Controllers (`AdminController`, `FormController`, `PublicController`, `DynamicApiController`, `Part`) to use `BaseModel` helpers, ensuring 100% of application queries are logged to the Debug Bar.
- **CMS**: Helper `Part::load` now uses `BaseModel` for database access.

### Fixed
- **CMS**: Fixed "Unexpected reserved word" syntax error in `CMS.js` caused by malformed `async/await` structure and duplicate object keys in `setup()`.
- **Admin**: Fixed `AdminController` generic query execution to log correctly.

## [v0.32.0] - 2025-12-14
### Added
- **Settings**: Robots.txt Management. Admins can now edit the `robots.txt` content directly from Site Settings.
- **Settings**: Site Language. Admins can configure the global `<html lang="...">` attribute.
- **Frontend**: Dynamic `robots.txt` serving via `PublicController`.
- **UI**: Added collapsible tips for `robots.txt` configuration in Site Settings to guide users.

## [v0.31.0] - 2025-12-14
### Added
- **Settings**: Site Settings implementation. Admins can now configure Site Title, Description, Keywords, Favicon, and Logo via the dashboard.
- **UI**: Added "Logo Upload" to Site Settings.
- **UI**: Dynamic Admin Header. Admin dashboard now displays the configured Site Title and Logo.
- **Frontend**: `MultiSitePanel` for managing multiple site databases.
- **Frontend**: "Launch App" link in the public header now dynamically points to the configured App URI.

### Fixed
- **Install**: Installation wizard now correctly captures initial Site Title and Description.
- **Regex**: Fixed invalid regex pattern in `MultiSitePanel` for domain validation.

## [v0.30.1] - 2025-12-14
### Fixed
- **Cache**: Segregated `Asset` and `Template` cache directories by site ID (`my-data/cache/min/domain.com`) to prevent cache collisions in multi-site setups.

## [v0.30.0] - 2025-12-14
### Added
- **Core**: Multi-Site Support. Single installation can now serve multiple domains using isolated databases per site (`One Database Per Site` architecture).
- **Core**: `SiteManager` class for handling domain-to-database resolution.
- **CLI**: `site` command group (`site:create`, `site:list`) for managing multi-site installations.
- **CLI**: Global `--site={domain}` flag support for identifying the target database for any CLI command.
- **Docs**: Added `docs/devops/multi_site.md`.

## [v0.28.2] - 2025-12-14
### Fixed
- **Seeder**: Fixed issue where `/app` page template was incorrectly overwritten to `default_site` during installation/seeding. `Seeder` now respects existing template slugs.

## [v0.29.2] - 2025-12-14
### Changed
- **UX**: Removed native `confirm()` popups from Delete actions and Logout for a smoother, instant experience.

## [v0.29.1] - 2025-12-14
### Added
- **Debug**: Added **Tasks** tab to visualize Framework task performance.
- **Debug**: Added **Logout** button (⏻) to toolbar header.
- **Debug**: Displayed current **Username** and **Level** in toolbar header.

## [v0.29.0] - 2025-12-14
### Added
- **Debug**: Interactive Debug Toolbar for Admins (`v-if="admin"`).
    - **SQL**: Real-time logging of executed SQL statements and their duration.
    - **Request**: Insights into Matched Route, Controller, and Parameters.
    - **Performance**: Metrics for total execution time and memory usage.
- **Core**: `LoggedPDO` database wrapper to intercept and profile SQL queries.
- **Core**: `Debug` singleton for collecting application telemetry.
- **Docs**: Added `docs/debug_toolbar.md`.

## [v0.28.0] - 2025-12-14
### Added
- **Templates**: Enhanced Code Editor with Ace Editor (syntax highlighting, autocomplete).
- **Templates**: Partials system (`cms_partials` table) for reusable PHP code snippets.
- **Templates**: File Explorer sidebar in Code Editor for managing Main Template and Partials.
- **Templates**: Developer Tips panel with quick reference for helpers and variables.
- **Core**: Automatic class aliasing system - use `Part`, `Page`, `User` without namespaces.
- **Core**: `Part` helper class with `Part::in('name')` syntax for including partials.
- **Docs**: Added comprehensive `docs/templating.md` documentation.
- **CLI**: Added Ace Editor to `vendor:update` command.

### Changed
- **Templates**: Replaced basic textarea with Ace Editor for PHP code editing.
- **Templates**: All Ace Editor files now served locally (no CDN dependency).

## [v0.27.0] - 2025-12-13
### Added
- **Templates**: Full PHP Template Management System. Admins can now Create, Read, Update, and Delete PHP templates via the CMS.
- **Templates**: Hybrid Editing Modes. Toggle between "Visual Builder" (No-Code, generates PHP wrapper) and "Code Editor" (Developer, direct PHP) for templates.
- **Templates**: `cms_templates` persistence. Templates can now be stored in the database, overriding file-based templates if desired.
- **CMS**: Updated `PublicController` to compile Visual Template JSON into PHP and cache it for high-performance rendering.
- **CMS**: Updated `CMS.js` with a robust multi-mode editor for templates.

### Added
- **Assets**: Smart Asset Pipeline. On-the-fly minification and caching for CSS and JS assets via `/min/` routes.
- **Assets**: `AssetController` to handle asset serving, caching, and fallback logic (checking `js` folders for CSS).
- **Assets**: `Asset::url()` helper to generate versioned, minified URLs.
- **Docs**: Updated `docs/performance.md` and `docs/docker_deployment.md` with Nginx FastCGI caching guide.

### Refactored
- **Assets**: Moved public assets from `www/` to `resources/` to prevent direct access and enforce the minification pipeline.
- **Docker**: Optimized Nginx configurations in `docker/deployment` and `docker/multimedia` to cache minified assets.
- **UI**: Standardized all Delete buttons to use standard red styling (`.btn-danger`) across all admin panels.
- **UI**: Replaced Google Fonts with locally hosted "Outfit" font files.

### Fixed
- **Assets**: Fixed `Asset::url` to correctly handle query strings (e.g., `?v=2`).
- **Assets**: Fixed 404 errors for vendor assets by implementing a CSS-in-JS folder fallback and image passthrough.
- **Map**: Fixed hardcoded asset paths in `MapPanel.js` for Leaflet and Globe.gl.

## [v0.26.1] - 2025-12-13
### Added
- **CMS**: Integrated `ImageSelector` into `SlotEditor` for easy image selection in templates.

## [v0.26.0] - 2025-12-13
### Added
- **Media**: Native AVIF support. Automatically prioritizes AVIF conversion if supported by the server, falling back to WebP.
- **Media**: Added Media Cache API (`GET /api/media/cache`, `POST /api/media/cache/clear`) for managing generated image assets.
- **UI**: New `ImageSelector` Vue component.
    - Replaces native file inputs with a rich gallery/upload modal.
    - Supports Drag & Drop.
    - Optimized with server-side thumbnail generation.
    - Integrated into CMS Editor (Featured Image, Content, Header).
- **Docs**: Added `docs/roadmap.md` outlining future development plans.

## [v0.25.0] - 2025-12-12
### Added
- **Chat**: Real-time user-to-user messaging system (`ChatPanel.js`, `ChatController`, `messages` table).
- **Templates**: Slot-Based Templating System.
    - Added "Properties" panel to `TemplateBuilder` for defining named slots and column counts (1-12).
    - Added `SlotEditor` for simplified page content editing based on template slots.
- **CMS**: Public Page Renderer. Pages created via templates are now rendered as HTML at `/page/:slug` (`PublicController`).

## [v0.24.0] - 2025-12-12
### Added
- **CLI**: Added `db:save` command to snapshot the database into `my-data/backups/`.
- **CLI**: Added `save:all` command to snapshot the database and zip the entire `my-data` folder to `backups/`.
- **CLI**: Documented `db:export` and `db:import` in help text.
- **Hooks**: Added `app_task_before` and `app_task_after` generic hooks, and dynamic hooks based on step ID (e.g., `app_task_before_{step}`) and task name (e.g., `app_task_before_GaiaAlpha_Framework_loadPlugins`).
- **Feature**: Added Admin Webshell (Console) to dashboard for executing CLI commands.

## [v0.23.0] - 2025-12-11
### Added
- **Core**: Added automatic class autoloading for plugins. Classes in `my-data/plugins/{Plugin}/class/` are mapped to `{Plugin}` namespace.
- **Docs**: Updated `docs/plugins.md` with autoloading guide.
- **Docs**: Added comparative performance analysis to `docs/performance.md`.

### Refactored
- **Core**: Moved plugin autoloader logic to `App::registerAutoloaders` for dynamic initialization.
- **Core**: Removed redundant configuration loading from `autoload.php`.

## [v0.22.1] - 2025-12-11
### Added
- **Core**: Introduced `Request` class for centralized input (`$_GET`, `$_POST`, `php://input`) handling.
- **Refactor**: `DynamicApiController` and `Framework` now use `Request` class for better testability and type safety.

## [v0.22.0] - 2025-12-11
### Added
- **Core**: Added `Response` class for centralized JSON output handling.
- **Hooks**: Added `response_json_before` hook to modify API responses globally.
- **Refactor**: Updated all controllers to use `Response::json` for consistent output and simplified testing.

## [v0.22.2] - 2025-12-11
### Added
- **CLI**: Added full suite of Video manipulation tools: `media:info`, `media:to-hls`, `media:fast-start`, `media:compress`.
- **CLI**: Added media extraction tools: `media:extract-frame`, `media:extract-frames`, `media:extract-audio`, `media:extract-video`.
- **CLI**: Added creative tools: `media:gif` (high quality palette generation), `media:watermark` (video overlay).

## [v0.21.0] - 2025-12-11
### Added
- **CLI**: Added `db:export` and `db:import` commands to backup and restore the full SQLite database.
- **CLI**: Added `media:process` and `media:batch-process` for advanced image manipulation (resize, rotate, filter, convert).
- **Docs**: Updated `README.md` with new CLI command usage.

## [v0.20.3] - 2025-12-11
### Fixed
- **AdminController**: Refactored to remove legacy model instantiation and use `DbController::getPdo()` directly, fixing deprecation warnings and aligning with static architecture.

## [v0.20.2] - 2025-12-11
### Docs
- **Architecture**: Added comprehensive **Routes Map** and **Route Prefix Strategy** analysis to `docs/architecture.md`.
- **Architecture**: Documented reserved namepsaces strategy for conflict prevention.

## [v0.20.1] - 2025-12-11
### Docs
- **Architecture**: Significantly updated `Database Schema` section in `docs/architecture.md` to include all tables (`cms_templates`, `forms`, `map_markers`, `menus`) and recent column additions.

## [v0.20.0] - 2025-12-11
### Added
- **Architecture**: Implemented Controller Ranking System (`getRank()`) to deterministically order route registration. 
- **Architecture**: Added `Framework::sortControllers()` task to `App` lifecycle.
- **Docs**: Added "Routing Strategy" section to `docs/architecture.md`.

### Refactored
- **Routing**: Moved frontend routing logic from `Router.php` to `ViewController`.
- **Routing**: `ViewController` now extends `BaseController` and runs with Rank 100 (lowest priority) to ensure catch-all routes don't mask API routes.

## [v0.19.0] - 2025-12-10
### Refactored
- **Database**: Migrated all models (`User`, `Page`, `Template`, `Todo`, `MapMarker`, `DataStore`) to use static database access via `DbController::getPdo()`.
- **Database**: Removed `DatabaseTrait` and the `Trait` directory.
- **Routing**: Updated `Router::dispatch` to correctly handle controllers returning void.
- **API**: Standardized `SettingsController` to support both `/api/settings` and `/api/user/settings` before deprecating the legacy one.

### Fixed
- **Menu API**: Fixed 404 error on `GET /api/menus` by using correct regex `(\d+)` for route parameters instead of `{id}`.
- **Settings API**: Fixed 400 error by correcting the payload structure in `store.js` to match backend expectations.

## [v0.17.0] - 2025-12-10
### Added
- **Components**: Added `Modal.js`, a reusable Vue component for standard dialogs.
- **Composables**: Added `useCrud.js`, a reusable composable for standard fetch/create/update/delete API logic.

### Refactored
- **Admin**: Refactored `UsersAdmin` to use `Modal` and `useCrud`, reducing code by ~30%.
- **Admin**: Refactored `DatabaseManager` to use `Modal` and `useSorting` for consistent behavior.
- **Modularity**: Removed duplicated modal implementations and custom sorting logic across admin panels.

## [v0.16.0] - 2025-12-10
### Added
- **Architecture**: Introduced `store.js`, a custom Vue 3 Data Store for centralized state management (User, Theme, Navigation).

### Refactored
- **State**: Decoupled global logic from `site.js` into modular Store actions.
- **Components**: Updated `CMS.js`, `Login.js`, and `UserSettings.js` to consume global state directly from the Store, removing prop drilling.
- **Cleanup**: Simplifed `site.js` by removing local state management and extensive prop passing.

## [v0.15.1] - 2025-12-10
### Docs
- **Architecture**: Added "Vue App Architecture" section to `docs/architecture.md`.

## [v0.15.0] - 2025-12-10
### Added
- **Feature**: Completely redesigned Template Builder. 
- **UX**: New Split-View interface with Toolbox, Structure Tree, and Live Preview.
- **UX**: Precise Drag-and-Drop system allowing "Before", "After", and "Inside" placement for nested structures.
- **UI**: High-contrast, theme-aware styling for builder components.

## [v0.15.0] - 2025-12-10
### Added
- **Features**: CMS Templates Backend. Added `Template` model and database migration for storing reusable content templates.
- **UI**: Map Panel Markers Table. Added a sortable table view to list all map markers.
- **Admin**: Dashboard Statistics. Added live counters for Templates, Markers, and Pages in the Admin Dashboard.

### Changed
- **Refactor**: `CmsController` now handles template CRUD operations alongside pages.
- **UI**: Improved `MapPanel` with combined Map + Table layout.

## [v0.13.0] - 2025-12-10
### Added
- **CLI**: Added `user` command group (list, create, delete, update-password).
- **CLI**: Added `vendor:update` command to manage third-party assets (e.g., Leaflet, Globe.gl).

### Changed
- **Refactor**: Split monolithic `Cli` class into modular `TableCommands`, `FileCommands`, `MediaCommands`, `UserCommands`, and `VendorCommands`.
- **Refactor**: Implemented dynamic command dispatching and lazy dependency initialization for better performance.
- **Refactor**: Moved CLI help message to `templates/cli_help.txt`.

## [v0.12.0] - 2025-12-10
### Added
- **Feature**: 3D Earth Map. Users can toggle between 2D map and 3D globe view.
- **Feature**: Marker Updates. Users can drag existing markers to update their position.
- **Feature**: Markers Table. Added a table view below the map to list and navigate to markers.

### Improved
- **Infrastructure**: Switched from CDN to local Leaflet assets for better reliability and offline support.

## [v0.11.0] - 2025-12-10
### Added
- **Feature**: Map Markers. Users can view a map and add persistent markers.
- **Docs**: Added `docs/map_feature.md` and updated architectural documentation.

### Fixed
- **Bug**: Resolved 401 Unauthorized error preventing app load by adjusting `ApiBuilderController` middleware.
- **Bug**: Fixed `MapMarker` model crash by switching to direct PDO usage.

### Improved
- **Performance**: Leaflet.js is now lazy-loaded only when the Map panel is accessed.

## [v0.10.0] - 2025-12-10
### Added
- **Feature**: Admin API Builder. Enabling dynamic REST API generation for any database table via Admin Dashboard.
- **Admin**: New "API Builder" panel in Admin Dashboard.
- **Architecture**: `DynamicApiController` generic CRUD implementation with pagination and filtering.
- **Config**: `api-config.json` support via `ApiBuilderController`.

## [v0.9.0] - 2025-12-10

### Changed
- **Architecture**: Introduced `Framework` class to handle controller loading and route registration.
- **Refactor**: `App` class now separates web and CLI setup (`web_setup`, `cli_setup`).
- **Refactor**: Combined `Router` and `App` logic into `Framework` tasks.
- **Refactor**: `Cli` class converted to static methods and integrated with `App::run`.
- **Refactor**: Removed `Router` instance dependency injection in `registerRoutes`.
- **Technical**: `cli.php` now uses `App::run` for standardized execution.

## [v0.8.0] - 2025-12-10

### Changed
- **Refactor**: Converted `App` and `Router` to static classes for streamlined access.
- **Refactor**: Introduced `Env` class for centralized global state and configuration.
- **Refactor**: Moved Database initialization to `DbController` and Session start to `AuthController`.
- **Refactor**: Updated controller loading logic to be dependency-free in constructors.
- **Technical**: Replaced `App::$rootDir` with `Env` based path management across CLI and Web.

## [v0.7.0] - 2025-12-09

### Added
- **Features**: Added "Todo Colors" functionality. Users can now assign colors to todos.
- **Features**: Added "Default Todo Duration" user preference to prefill end dates.
- **UI**: Added `ColorPicker` component with Custom Palette and HSV controls.
- **UI**: Added "Todo Color Palette" manager in User Settings.
- **Views**: Calendar and Gantt views now visualize todo colors.

## [v0.6.0] - 2025-12-08

### Added
- **Form Builder**: Added customizable Submit button label.
- **Form Builder**: Added "Rows" setting for Text Area fields.
- **Admin**: Added "Forms" and "Submissions" stats to Admin Dashboard.
- **UX**: Improved visual contrast for form inputs in dark mode.
- **UX**: Added custom animated checkbox styling.

### Changed
- **Tech**: Decentralized route registration from `App.php` to individual controllers.
- **Tech**: Implemented `registerRoutes()` method across the controller layer.

## [v0.5.0] - 2025-12-08

### Added
- **UI**: Added Column Sorting to User Management and CMS panels.
- **UI**: Added expandable 100% width layout for all Admin Panels.
- **Technical**: Introduced `useSorting` composable and `SortTh` component for standardized table features.

### Changed
- **UI**: Harmonized `TodoList` layout to match the standard Admin Panel design.
- **UI**: Refined Top Menu layout (Theme toggle position, iconic Logout).
- **Refactor**: Migrated manual sorting logic in Admin panels to shared components.

### Fixed
- **Bugs**: Fixed Vue template syntax errors in `DatabaseManager`, `UsersAdmin`, `CMS`, and `UserSettings`.
- **Bugs**: Fixed layout constraints preventing panels from using full screen width.

## [v0.4.0] - 2025-12-08
