# Debug Toolbar

Gaia Alpha includes a built-in Debug Toolbar to assist developers and administrators in analyzing application performance and behavior.

## Overview

The Debug Toolbar provides real-time insights into the current request, including:
- **SQL Queries**: A list of all executed SQL statements with their execution duration.
- **Request Info**: Details about the matched route, controller method, and parameters.
- **Performance Metrics**: Total execution time and memory usage (current vs peak).
- **Globals**: Inspection of `$_GET` and `$_POST` data.

## Usage

The toolbar is automatically injected into the bottom of the page for users with **Admin** privileges (Level >= 100).

1.  **Log in** as an administrator.
2.  Visit any public-facing page or admin panel.
3.  Click the **Gaia Debug** bar at the bottom of the screen to expand it.
4.  Use the tabs (**SQL Queries**, **Request**, **Globals**) to switch between different data views.
5.  Click the arrow icon or the header to minimize the toolbar.

## Architecture

The debug system is composed of three main components:

### 1. Backend Collector (`GaiaAlpha\Debug`)
The `Debug` class acts as a central singleton collector.
- Hooks into `App::run` to initialize timers.
- Hooks into the `Router` to capture route information.
- Exposes `logQuery` method for database timing.

### 2. Database Wrapper (`LoggedPDO`)
To capture SQL queries without modifying application logic, we extend the native `PDO` class.
- `GaiaAlpha\Database\LoggedPDO` extends `\PDO`.
- It overrides `query`, `exec`, and `prepare`.
- Timing data is automatically sent to the `Debug` collector.

### 3. Frontend Component (`DebugToolbar.js`)
The UI is a **Vue 3** component injected directly into the HTML response by the `ViewController` (and `PublicController`).
- It extracts JSON-encoded debug data from `window.GAIA_DEBUG_DATA`.
- It relies on `Vue` being available globally (injected via ES modules).

## Troubleshooting

- **Toolbar not showing?** Ensure you are logged in as an Admin.
- **"Vue is not defined"?** The toolbar requires Vue. The controller injection logic handles this, but if you have a custom controller that bypasses `render()`, you may need to manually inject the toolbar logic.
