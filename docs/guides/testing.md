# Testing with Audit Routes

The Audit Routes package provides built-in PHPUnit assertions to integrate route security checks directly into your test suite. This allows you to enforce route security standards as part of your continuous integration pipeline.

## Setup

Include the `AssertsAuditRoutes` trait in your test classes to access audit assertions:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use MyDev\AuditRoutes\Testing\AssertsAuditRoutes;

class RouteSecurityTest extends TestCase
{
    use AssertsAuditRoutes;

    // Your test methods here
}
```

## Test Coverage Assertions

### Assert Routes Are Tested

Verify that routes have corresponding test coverage:

```php
// Assert that all routes are covered by tests
$this->assertRoutesAreTested(['*']);

// Assert specific routes are tested
$this->assertRoutesAreTested(['users.index', 'users.show', 'orders.create']);

// Assert routes with exclusions
$this->assertRoutesAreTested(['api.*'], ignoredRoutes: ['api.health', 'api.status']);
```

### Assert Individual Route Testing

Check that a specific route has test coverage:

```php
// Assert a single route is tested
$this->assertRouteIsTested('welcome');

// Common usage for critical routes
$this->assertRouteIsTested('admin.users.destroy');
$this->assertRouteIsTested('billing.process');
```

## Middleware Assertions

### Assert Routes Have Middleware

Verify that routes implement required middleware:

```php
// Assert all routes have authentication middleware (with exclusions)
$this->assertRoutesHaveMiddleware(
    ['*'],
    ['auth'],
    ignoredRoutes: ['welcome', 'login', 'register', 'api.*']
);

// Assert API routes have token authentication
$this->assertRoutesHaveMiddleware(['api.*'], ['auth:sanctum']);

// Assert admin routes have multiple middleware
$this->assertRoutesHaveMiddleware(
    ['admin.*'],
    ['auth', 'verified', 'role:admin']
);
```

### Assert Individual Route Middleware

Check middleware on specific routes:

```php
// Assert a specific route has required middleware
$this->assertRouteHasMiddleware('api.user.index', ['auth:sanctum']);

// Assert admin routes have proper protection
$this->assertRouteHasMiddleware('admin.users.destroy', ['auth', 'can:delete,user']);
```

## Custom Audit Assertions

### Assert Audit Routes Pass

Run custom auditors and assert compliance:

```php
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;

// Assert all routes pass custom audit with default benchmark (0)
$this->assertAuditRoutesOk($routes, [
    PolicyAuditor::make(),
    MiddlewareAuditor::make(['auth'])
]);

// Assert with custom benchmark
$this->assertAuditRoutesOk(
    ['admin.*'],
    [PolicyAuditor::make()],
    'Admin routes must have proper authorization',
    benchmark: 25
);
```

### Assert Routes Fail Audits

Use negative weights to assert auditors should NOT apply:

```php
// Assert that public routes don't have authentication requirements
$this->assertAuditRoutesOk(
    ['welcome', 'about', 'contact'],
    [MiddlewareAuditor::make(['auth'])->setWeight(-1)],
    'Public routes should not require authentication'
);
```

## Integration Patterns

### Security Test Suite

Create comprehensive security test suites:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use MyDev\AuditRoutes\Testing\AssertsAuditRoutes;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;

class RouteSecurityComplianceTest extends TestCase
{
    use AssertsAuditRoutes;

    /** @test */
    public function all_routes_have_test_coverage()
    {
        $this->assertRoutesAreTested(['*'], ignoredRoutes: [
            'telescope*', 'debugbar.*', 'ignition.*'
        ]);
    }

    /** @test */
    public function api_routes_require_authentication()
    {
        $this->assertRoutesHaveMiddleware(
            ['api.*'],
            ['auth:sanctum'],
            ignoredRoutes: ['api.health', 'api.status', 'api.documentation']
        );
    }

    /** @test */
    public function admin_routes_have_proper_authorization()
    {
        $this->assertAuditRoutesOk(
            ['admin.*'],
            [
                PolicyAuditor::make()->setWeight(25),
                MiddlewareAuditor::make(['auth', 'verified'])->setWeight(25)
            ],
            'Admin routes must have authentication and authorization',
            benchmark: 50
        );
    }

    /** @test */
    public function public_routes_remain_accessible()
    {
        $publicRoutes = ['welcome', 'login', 'register', 'password.*'];

        $this->assertAuditRoutesOk(
            $publicRoutes,
            [MiddlewareAuditor::make(['auth'])->setWeight(-1)],
            'Public routes should not require authentication'
        );
    }
}
```

### Environment-Specific Tests

Create tests that adapt to different environments:

```php
/** @test */
public function routes_meet_environment_security_standards()
{
    $benchmark = match (app()->environment()) {
        'production' => 75,   // Strict security
        'staging' => 50,      // Moderate security
        'local' => 25,        // Relaxed for development
        default => 25
    };

    $this->assertAuditRoutesOk(
        ['*'],
        [
            PolicyAuditor::make()->setWeight(30),
            PhpUnitAuditor::make()->setWeight(25),
        ],
        "Routes must meet {$benchmark} point security standard",
        benchmark: $benchmark
    );
}
```

### Custom Auditor Testing

Test your custom auditors work correctly:

```php
/** @test */
public function custom_security_headers_auditor_works()
{
    // Assuming you have a custom SecurityHeadersAuditor
    $this->assertAuditRoutesOk(
        ['api.*'],
        [new SecurityHeadersAuditor()],
        'API routes must implement security headers',
        benchmark: 1
    );
}
```

## CI/CD Integration

### GitHub Actions

Integrate route security checks into your workflow:

```yaml
# .github/workflows/security-audit.yml
name: Route Security Audit

on: [push, pull_request]

jobs:
  security-audit:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run Route Security Tests
        run: ./vendor/bin/phpunit --testsuite=Security
```

### Laravel Testing

Add route security as part of your regular test suite:

```php
// In your base TestCase or specific test class
protected function setUp(): void
{
    parent::setUp();

    // Run basic security audit on every test run
    if (app()->environment('testing')) {
        $this->runBasicSecurityAudit();
    }
}

private function runBasicSecurityAudit(): void
{
    $this->assertRoutesAreTested(['*'], ignoredRoutes: ['telescope*']);
    $this->assertRoutesHaveMiddleware(['admin.*'], ['auth']);
}
```

## Best Practices

### Test Organization

1. **Separate Security Tests**: Create dedicated test classes for security audits
2. **Environment Awareness**: Adjust expectations based on environment
3. **Granular Assertions**: Test specific aspects rather than everything at once
4. **Clear Messages**: Provide descriptive failure messages

### Performance Considerations

1. **Selective Testing**: Don't audit all routes in every test
2. **Cache Results**: Use static properties to cache audit results across tests
3. **Group Assertions**: Combine related assertions when possible

### Error Handling

```php
/** @test */
public function security_audit_provides_clear_failures()
{
    try {
        $this->assertAuditRoutesOk(['*'], [PolicyAuditor::make()], benchmark: 100);
    } catch (AssertionFailedError $e) {
        // Assert that the error message contains useful debugging info
        $this->assertStringContainsString('routes scored below benchmark', $e->getMessage());
        $this->assertStringContainsString('Route:', $e->getMessage());
    }
}
```

## Pest Support

While Pest support is planned for future releases, you can currently use these assertions in Pest tests by including the trait:

```php
// When Pest support is available
it('ensures all routes are tested', function () {
    expect(routes())->toBeTestedInAuditRoutes(['*']);
});

it('requires authentication on protected routes', function () {
    expect(['admin.*'])->toHaveMiddlewareInAuditRoutes(['auth']);
});
```

## Next Steps

- **[CI Integration](ci-integration.md)**: Set up automated security audits in your deployment pipeline
- **[Custom Auditors](custom-auditors.md)**: Build application-specific security checks
- **[Advanced Usage](advanced-usage.md)**: Complex testing patterns and enterprise configurations
- **[Basic Usage](basic-usage.md)**: Learn about command-line audit tools
- **[Configuration Guide](../getting-started/configuration.md)**: Configure testing and benchmark settings
- **[Troubleshooting](troubleshooting.md)**: Resolve common testing and configuration issues