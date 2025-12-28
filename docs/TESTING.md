# Testing in Gaia Alpha

This guide outlines how to run and create tests for the Gaia Alpha CMS, specifically focusing on the regression testing suite.

## Running Tests

To run the full regression test suite, execute the following command from the project root:

```bash
php tests/run.php tests/Regression
```

You can also run a specific test file:

```bash
php tests/run.php tests/Regression/Bug000_ExampleTest.php
```

## Creating Regression Tests

Regression tests are used to verify bug fixes and prevent re-occurrence.

1.  Create a new file in `tests/Regression/` with the naming format `Bug<ID>_<Description>Test.php`.
2.  Define a class that extends `GaiaAlpha\Tests\Framework\ApiTestCase` (for API/Integration tests) or `GaiaAlpha\Tests\Framework\TestCase` (for Unit tests).
3.  Implement test methods prefixed with `test`.

### Example

```php
<?php

namespace GaiaAlpha\Tests\Regression;

use GaiaAlpha\Tests\Framework\ApiTestCase;
use GaiaAlpha\Tests\Framework\Assert;

class Bug123_LoginFixTest extends ApiTestCase
{
    public function testLoginRespectsSessionWait()
    {
        // ... Reproduce bug or verify fix ...
        Assert::assertTrue(true);
    }
}
```

## Test Runner

The custom test runner is located at `tests/run.php`. It handles bootstrapping the application environment and executing tests found in the provided paths.

## UI Test Runner

Gaia Alpha includes a dedicated UI Test Runner for testing frontend components (Vue 3).

### Running UI Tests
1.  Start the test server:
    ```bash
    php tests/js/server.php
    ```
2.  Open the runner in your browser:
    [http://localhost:8001/?mode=auto](http://localhost:8001/?mode=auto)

The runner will automatically discover and execute tests defined in `tests/js/`.

### Directory Structure
-   `tests/js/admin/`: Tests for Admin Panel components.
-   `tests/js/cms/`: Tests for CMS components.
-   `tests/js/plugins/`: Tests for Plugins.
-   `tests/js/framework/`: Core test framework utilities.

For details on writing UI tests, see the [UI Testing Pattern](patterns/testing_ui.md).

## Developer Workflow

See [Testing Workflow Patterns](patterns/testing_workflow.md) for detailed instructions on TDD and Regression testing patterns.
