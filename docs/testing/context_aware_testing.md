# Context-Aware Testing

Many components in Gaia Alpha change their behavior based on the current request context (e.g., `api`, `admin`, `public`). Testing these components requires mocking the environment to simulate different scenarios.

## Bypassing CLI Detection

By default, the `Request::context()` method returns `cli` when running from the command line. To test URL-based context detection, you must define the `GAIA_TEST_HTTP` constant in your test's `setUp()` method.

```php
public function setUp(): void
{
    if (!defined('GAIA_TEST_HTTP')) {
        define('GAIA_TEST_HTTP', true);
    }
}
```

## Mocking Request URI

The context detection logic relies on `$_SERVER['REQUEST_URI']`. You can manually set this variable in your test methods to simulate requested paths.

```php
public function testApiContextDetection()
{
    $_SERVER['REQUEST_URI'] = '/@/api/todos';
    Assert::assertEquals('api', Request::context());
}

public function testAdminContextDetection()
{
    $_SERVER['REQUEST_URI'] = '/@/admin/dashboard';
    Assert::assertEquals('admin', Request::context());
}
```

## Testing Configurable Prefixes

If your code adapts to custom prefixes, you can use `Env::set()` to mock these configurations during tests.

```php
public function testCustomApiPrefix()
{
    Env::set('api_prefixes', ['/v1/custom']);
    $_SERVER['REQUEST_URI'] = '/v1/custom/endpoint';
    
    Assert::assertEquals('api', Request::context());
}
```

## Plugin Filtering Verification

You can verify that plugins are correctly filtered by simulating the logic used in `Framework::loadPlugins()`:

```php
public function testPluginContextFiltering()
{
    $_SERVER['REQUEST_URI'] = '/@/api/data';
    $context = Request::context(); // returns 'api'
    
    $pluginConfig = ['context' => 'admin'];
    
    $shouldLoad = ($pluginConfig['context'] === 'all' || $pluginConfig['context'] === $context);
    Assert::assertFalse($shouldLoad, "Admin plugin should not load in API context");
}
```

## Mocking Authenticated Requests (JWT)

Testing authenticated endpoints is significantly easier with JWTs than with Sessions. You do not need to manage a cookie jar. Simply generate a token and inject it into the `HTTP_AUTHORIZATION` server variable.

```php
public function testAuthenticatedApiEndpoint()
{
    // 1. Generate a valid token (requires JwtAuth plugin)
    $token = \JwtAuth\Service::generateToken([
        'user_id' => 1,
        'username' => 'test_admin',
        'level' => 100
    ]);

    // 2. Inject into the Request
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    $_SERVER['REQUEST_URI'] = '/@/api/protected-resource';
    
    // 3. Dispatch
    $response = $this->dispatch();
    
    // 4. Verify
    Assert::assertEquals(200, $response->statusCode);
}
```

## Best Practices
- **Reset State**: Always reset server variables or environment settings in `tearDown()` if they might affect other tests.
- **Isolate Logic**: Focus on testing the detection logic separately from the functional behavior it triggers.
