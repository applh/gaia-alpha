# Changelog

All notable changes to this project will be documented in this file.
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
