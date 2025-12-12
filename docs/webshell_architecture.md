# Webshell Architecture

The **Admin Webshell (Console)** enables authorized administrators to execute CLI commands directly from the web interface. This document outlines the architectural decisions, security measures, and execution flow.

## Overview

The Webshell bridges the gap between the PHP Web environment (Apache/Nginx + PHP-FPM) and the System Shell (CLI). It allows the execution of `php cli.php` commands via a REST API endpoint.

### Components

1.  **Frontend**: `ConsolePanel.js` (Vue 3)
    - Provides a terminal-like interface.
    - Maintains command history (up/down navigation).
    - Sends POST requests to the backend.

2.  **Backend**: `ConsoleController.php`
    - Endpoint: `POST /api/console/run`
    - Input: `{"command": "db:list users"}`
    - Output: JSON output captured from stdout/stderr.

3.  **Command Runner**: `System::exec`
    - Standardized wrapper for PHP's `exec` function.
    - Hooks for plugins (`system_exec_before`, `system_exec_after`).

## Execution Flow

1.  **Request**: User types `db:list users` in the browser.
2.  **Frontend**: Sends request to `/api/console/run`.
3.  **Auth Check**: `ConsoleController` verifies the user is Level 100 (Admin).
4.  **Sanitization**:
    - The controller accepts the raw command string.
    - It parses arguments, handling quoted strings.
    - It reconstructs the command using `System::escapeArg()` for every argument.
    - The executable is hardcoded to `php cli.php`.
5.  **Execution**:
    - Command constructed: `php /path/to/cli.php 'db:list' 'users' 2>&1`
    - `System::exec()` runs the command.
6.  **Response**: The raw output lines are joined and sent back as JSON.

## Security Model

Allowing shell execution from the web is inherently risky. Gaia Alpha mitigates these risks via:

### 1. Hardcoded Entry Function
The Webshell **cannot** run arbitrary system commands (like `rm -rf` or `cat /etc/passwd`). It is strictly limited to invoking the application's own CLI entry point (`cli.php`).
- **Allowed**: `php cli.php [args]`
- **Blocked**: `bash script.sh`, `ls -la`, etc.

### 2. Argument Escaping
All user input is treated as arguments to `cli.php`. We use `escapeshellarg()` on every received token to prevent command injection chaining (e.g., `db:list; rm -rf /`).

### 3. Authentication & Authorization
Only users with `level >= 100` (Admin) are permitted to access the `ConsoleController`. This check is performed before any input processing.

### 4. Input Validation
Basic tokenization ensures the input structure matches the expected `command [args...]` format.

## Limitations

- **Interactive Commands**: The webshell is non-interactive. It cannot handle commands that require user input (stdin) during execution (e.g., `y/n` prompts).
- **Timeouts**: Long-running commands (like complex video encoding) may timeout depending on the PHP-FPM `max_execution_time` setting. For heavy tasks, it is still recommended to use a real SSH terminal if possible, or use the `Schedule` system (planned).
