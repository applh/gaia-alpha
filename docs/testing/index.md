# Testing in Gaia Alpha

Gaia Alpha uses a **custom, lightweight testing framework** designed to be dependency-free.
This ensures the project remains simple and easy to deploy without heavy `vendor` or `node_modules` folders.

## Overview

- **Backend Tests (PHP)**: Located in `tests/`. Run via `php tests/run.php`.
- **Frontend Tests (JS)**: Located in `tests/js/`. Run by opening `tests/js/index.html` in your browser.

## Guides

- [Backend Testing Guide](backend.md)
- [Frontend Testing Guide](frontend.md)

## Quick Start
1. **Run Backend Tests**:
   ```bash
   php tests/run.php
   ```

2. **Run Frontend Tests**:
   Start the test server: `php scripts/test_server.php`
   Then open: `http://localhost:8001/tests/js/index.html`
