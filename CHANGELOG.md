# Changelog

All notable changes to this project will be documented in this file.
## [v0.4.0] - 2025-12-08

### Added
- **Todo List**: Implemented Drag and Drop support.
    - **Reordering**: Drag items up or down to change their order in the list.
    - **Reparenting**: Drag an item onto another to make it a child.
    - **Visual Feedback**: visual indicators for drop targets (top, bottom, inside).
- **Database**: Added `position` column to `todos` table for custom sorting.
- **API**: Added `POST /api/todos/reorder` endpoint.


## [v0.3.0] - 2025-12-08

### Added
- **Admin Database Manager**: Added sorting feature to the table browser. Click on column headers to sort data ascending or descending.
- **Admin Database Manager**: Implemented modal-based CRUD operations for better user experience.
- **Todo List**: Added recursive rendering to support infinite nesting of todo items.
- **Todo List**: Parenting logic updated to allow any todo item to be a parent of another.
- **Database**: Added migration `005_add_parent_id.sql` to add missing `parent_id` column to `todos` table.

### Changed
- **Todo List**: Improved error handling during todo creation.
- **Admin Database Manager**: Actions column is now sticky to the left for better visibility on wide tables.
- **UI**: General CSS improvements for tables and modals.

### Fixed
- Fixed an issue where creating a new todo would fail silently due to missing database column.
- Fixed an issue where the frontend did not display the newly created todo immediately.
