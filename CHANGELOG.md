# Changelog

All notable changes to this project will be documented in this file.
## [v0.12.0] - 2025-12-10
### Added
- **Feature**: Marker Updates. Users can drag existing markers to update their position.
- **Feature**: Markers Table. Added a table view below the map to list and navigate to markers.
- **CLI**: Added `vendor:update` command to manage third-party assets (e.g., Leaflet).

### Changed
- **Refactor**: Split monolithic `Cli` class into modular `TableCommands`, `FileCommands`, `MediaCommands`, and `VendorCommands`.

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
