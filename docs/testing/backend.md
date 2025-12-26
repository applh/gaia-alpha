# Backend Testing Guide

Our PHP testing framework mimics standard xUnit patterns (like PHPUnit) but is built from scratch to remain lightweight and dependency-free.

## File Structure
- Tests must be located in the `tests/` directory.
- Test files must end in `Test.php` (e.g., `MyPluginTest.php`).
- Test classes must extend `GaiaAlpha\Tests\Framework\TestCase`.

## Writing a Test

```php
namespace GaiaAlpha\Tests\Plugins;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class TodoTest extends TestCase {
    
    public function setUp() {
        // Run before each test
    }

    public function testSomething() {
        $value = true;
        Assert::assertTrue($value, "Value should be true");
    }
}
```

## Available Assertions
The `GaiaAlpha\Tests\Framework\Assert` class provides:

- `assertTrue($condition, $msg)`
- `assertFalse($condition, $msg)`
- `assertEquals($expected, $actual, $msg)`
- `assertNotEquals($expected, $actual, $msg)`
- `assertCount($expectedCount, $array, $msg)`
- `assertArrayHasKey($key, $array, $msg)`
- `assertStringContains($needle, $haystack, $msg)`

## Test Controller
GaiaAlpha provides a `TestController` to help run tests via the browser or CLI.
- **Browser**: Navigate to `/tests` (if enabled in `config.php`) to see a dashboard of test results.
- **CLI**: Run `php cli.php test:run` for automated suites.

## Running Tests
Execute the runner from the project root:
```bash
php tests/run.php
```

## See Also
- [Context-Aware Testing](./context_aware_testing.md): How to test code that relies on request contexts.
