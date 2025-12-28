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
    > [!IMPORTANT] 
    > Every bug fix **must** be accompanied by a regression test to prevent future regressions.


### 2. Refactoring or New Features
1.  **Run All Tests**: Before pushing your changes, run the full regression suite to ensure no existing functionality is broken.
    ```bash
    php tests/run.php tests/Regression
    ```
    
    You should also run Unit and Integration suites if your changes affect core logic:
    ```bash
    php tests/run.php tests/Unit
    php tests/run.php tests/Integration
    ```
### 3. Frontend/UI Testing
When working on Vue 3 components (`resources/js/components/` or plugins), use the UI Test Runner.

1.  **Write Tests**: Create a `.js` test file in `tests/js/`.
2.  **Run Runner**: Start `php tests/js/server.php` and open the runner in your browser.
3.  **Verify**: Ensure your component renders correctly and handles interactions.

For detailed patterns, see [UI Testing Pattern](testing_ui.md).
