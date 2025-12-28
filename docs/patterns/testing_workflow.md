# Testing Workflow Patterns

This document outlines the recommended workflow for running and adding tests in Gaia Alpha.

## Developer Workflow

When working on the codebase, adopt the following patterns to ensure stability:

### 1. Fixing a Bug
1.  **Reproduce**: Create a new test case in `tests/Regression/` that reproduces the bug.
2.  **Verify Failure**: Run the test to confirm it fails (establishing a baseline).
    ```bash
    php tests/run.php tests/Regression/BugXXX_MyBugTest.php
    ```
3.  **Fix**: Apply your fix in the codebase.
4.  **Verify Success**: Run the test again to confirm it passes.

### 2. Refactoring or New Features
1.  **Run All Tests**: Before pushing your changes, run the full regression suite to ensure no existing functionality is broken.
    ```bash
    php tests/run.php tests/Regression
    ```
2.  **Add Coverage**: If your new feature is critical, add a new regression/integration test for it.
