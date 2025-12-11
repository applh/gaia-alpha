# Changelog

All notable changes to this project will be documented in this file.
## [v0.21.0] - 2025-12-11
### Added
- **CLI**: Added `db:export` and `db:import` commands to backup and restore the full SQLite database.
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
