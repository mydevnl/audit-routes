# PHPUnit Assertions API Reference

Integrate route security validation directly into your test suite using PHPUnit assertions. These assertions allow you to enforce security standards as part of your continuous integration pipeline.

## Setup

Add the assertions trait to your PHPUnit test classes:

```php
<?php

use MyDev\AuditRoutes\Testing\Concerns\AssertsAuditRoutes;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    use AssertsAuditRoutes;

    // Your security tests here
}
```

The `AssertsAuditRoutes` trait provides access to all route security assertions.

## Core Assertions

### assertRoutesAreTested

Verify that routes have corresponding test coverage.

**Purpose**: Ensure routes are protected against regressions with automated tests.

**Signature**:
```php
protected function assertRoutesAreTested(
    iterable $routes,
    int $times = 1,
    array $ignoredRoutes = [],
    null|string|Closure $message = null,
    ?Closure $when = null
): static
```

**Parameters**:
- `$routes`: Route names/patterns to check
- `$times`: Minimum test count per route (default: 1)
- `$ignoredRoutes`: Routes to exclude from check
- `$message`: Custom failure message
- `$when`: Conditional filter for routes

**Usage Examples**:
```php
// Assert all routes have test coverage
public function test_all_routes_are_tested()
{
    $this->assertRoutesAreTested(['*']);
}

// Check specific routes
public function test_user_routes_are_tested()
{
    $this->assertRoutesAreTested([
        'users.index',
        'users.show',
        'users.store',
        'users.update',
        'users.destroy',
    ]);
}

// Require multiple tests per route
public function test_critical_routes_have_comprehensive_tests()
{
    $this->assertRoutesAreTested(
        ['orders.store', 'payments.process'],
        times: 3,  // Require at least 3 tests each
        message: 'Critical routes need comprehensive test coverage'
    );
}

// Conditional testing requirements
public function test_admin_routes_are_tested()
{
    $this->assertRoutesAreTested(
        ['*'],
        when: fn($route) => str_starts_with($route->getIdentifier(), 'admin.')
    );
}
```

---

### assertRouteIsTested

Verify a specific route has test coverage.

**Purpose**: Check individual routes for test protection.

**Signature**:
```php
protected function assertRouteIsTested(
    mixed $route,
    int $times = 1,
    ?string $message = null
): static
```

**Usage Examples**:
```php
// Single route test coverage
public function test_welcome_route_is_tested()
{
    $this->assertRouteIsTested('welcome');
}

// Route with specific test requirements
public function test_checkout_process_is_thoroughly_tested()
{
    $this->assertRouteIsTested(
        'checkout.process',
        times: 5,
        message: 'Checkout process requires extensive testing'
    );
}
```

---

### assertRoutesHaveMiddleware

Ensure routes implement required middleware for security compliance.

**Purpose**: Validate that routes have essential security middleware like authentication, authorization, or rate limiting.

**Signature**:
```php
protected function assertRoutesHaveMiddleware(
    iterable $routes,
    array $middleware,
    array $ignoredRoutes = [],
    null|string|Closure $message = null,
    ?Closure $when = null
): static
```

**Parameters**:
- `$routes`: Route names/patterns to check
- `$middleware`: Required middleware list
- `$ignoredRoutes`: Routes to exclude
- `$message`: Custom failure message
- `$when`: Conditional filter

**Usage Examples**:
```php
// All routes must have authentication
public function test_all_routes_require_authentication()
{
    $this->assertRoutesHaveMiddleware(
        ['*'],
        ['auth'],
        ignoredRoutes: ['welcome', 'login', 'register']
    );
}

// API routes require token authentication
public function test_api_routes_use_sanctum()
{
    $this->assertRoutesHaveMiddleware(
        ['*'],
        ['auth:sanctum'],
        ignoredRoutes: ['api.login'],
        when: fn($route) => str_starts_with($route->getIdentifier(), 'api.')
    );
}

// Admin routes need multiple middleware
public function test_admin_routes_have_security_stack()
{
    $this->assertRoutesHaveMiddleware(
        ['admin.*'],
        ['auth', 'verified', 'role:admin']
    );
}

// Rate limiting for public endpoints
public function test_public_api_has_rate_limiting()
{
    $this->assertRoutesHaveMiddleware(
        ['api.public.*'],
        ['throttle:100,1'],
        message: 'Public API endpoints must be rate limited'
    );
}
```

---

### assertRouteHasMiddleware

Check that a specific route implements required middleware.

**Purpose**: Validate individual route middleware configuration.

**Signature**:
```php
protected function assertRouteHasMiddleware(
    mixed $route,
    array $middleware,
    ?string $message = null
): static
```

**Usage Examples**:
```php
// Specific route middleware check
public function test_user_profile_requires_auth()
{
    $this->assertRouteHasMiddleware('profile.edit', ['auth']);
}

// API endpoint authentication
public function test_api_orders_require_sanctum()
{
    $this->assertRouteHasMiddleware(
        'api.orders.index',
        ['auth:sanctum'],
        'API orders endpoint must use Sanctum authentication'
    );
}
```

---

### assertAuditRoutesOk

Run custom auditors and assert all routes pass the specified benchmark.

**Purpose**: Create sophisticated security tests with custom auditor logic and scoring.

**Signature**:
```php
protected function assertAuditRoutesOk(
    iterable $routes,
    array $auditors,
    string|Closure $message,
    array $ignoredRoutes = [],
    int $benchmark = 0
): static
```

**Parameters**:
- `$routes`: Routes to audit
- `$auditors`: Array of auditor instances or classes
- `$message`: Failure message or closure
- `$ignoredRoutes`: Routes to exclude
- `$benchmark`: Minimum acceptable score

**Usage Examples**:
```php
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;

// Policy-based authorization check
public function test_user_routes_have_policies()
{
    $this->assertAuditRoutesOk(
        ['users.*'],
        [PolicyAuditor::make()],
        'User routes must implement authorization policies',
        benchmark: 1
    );
}

// Multi-auditor security validation
public function test_admin_routes_meet_security_standards()
{
    $this->assertAuditRoutesOk(
        ['admin.*'],
        [
            PolicyAuditor::make()->setWeight(25),
            PhpUnitAuditor::make()->setWeight(25),
        ],
        'Admin routes must meet high security standards',
        benchmark: 40
    );
}

// Negative weight to ensure absence of certain features
public function test_public_routes_not_permission_protected()
{
    $this->assertAuditRoutesOk(
        ['welcome', 'about', 'contact'],
        [PermissionAuditor::make()->setWeight(-1)],
        function($failedRoutes) {
            return 'Public routes should not require permissions: ' .
                   $failedRoutes->pluck('identifier')->join(', ');
        }
    );
}

// Complex conditional logic
public function test_api_routes_security_compliance()
{
    $message = function($failedRoutes) {
        $routeList = $failedRoutes->pluck('identifier')->join("\n  - ");
        return "Failed API routes:\n  - {$routeList}";
    };

    $this->assertAuditRoutesOk(
        ['*'],
        [
            MiddlewareAuditor::make(['auth:sanctum'])->setWeight(20),
            PhpUnitAuditor::make()->setWeight(15),
        ],
        $message,
        ignoredRoutes: ['api.login', 'api.register'],
        benchmark: 30
    );
}
```

## Integration Patterns

### Feature Test Integration

Combine security assertions with functional testing:

```php
class OrderManagementTest extends TestCase
{
    use AssertsAuditRoutes;

    public function test_order_workflow_security()
    {
        // Functional test
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('orders.store'), $this->validOrderData())
             ->assertStatus(201);

        // Security validation
        $this->assertRoutesHaveMiddleware(
            ['orders.store', 'orders.show'],
            ['auth']
        );

        $this->assertRoutesAreTested([
            'orders.store',
            'orders.show'
        ]);
    }
}
```

### Security Test Suite

Create dedicated security test classes:

```php
class ApplicationSecurityTest extends TestCase
{
    use AssertsAuditRoutes;

    public function test_authentication_coverage()
    {
        $this->assertRoutesHaveMiddleware(
            ['*'],
            ['auth'],
            ignoredRoutes: [
                'welcome', 'login', 'register',
                'password.request', 'password.reset'
            ]
        );
    }

    public function test_api_authentication()
    {
        $this->assertRoutesHaveMiddleware(
            ['*'],
            ['auth:sanctum'],
            when: fn($route) => str_starts_with($route->getIdentifier(), 'api.')
        );
    }

    public function test_comprehensive_test_coverage()
    {
        $this->assertRoutesAreTested(['*']);
    }

    public function test_admin_security_standards()
    {
        $this->assertAuditRoutesOk(
            ['admin.*'],
            [
                PolicyAuditor::make()->setWeight(30),
                PhpUnitAuditor::make()->setWeight(20),
            ],
            'Admin routes must meet strict security standards',
            benchmark: 40
        );
    }
}
```

### CI/CD Pipeline Integration

Use assertions in continuous integration:

```php
class SecurityComplianceTest extends TestCase
{
    use AssertsAuditRoutes;

    /**
     * @group security
     * @group ci
     */
    public function test_security_compliance_gate()
    {
        // Block deployment if security standards aren't met
        $this->assertAuditRoutesOk(
            ['*'],
            [
                PolicyAuditor::make()->setWeight(20),
                PhpUnitAuditor::make()->setWeight(15),
                MiddlewareAuditor::make(['auth'])->setWeight(10),
            ],
            'Application fails security compliance check',
            ignoredRoutes: config('audit-routes.ignored-routes'),
            benchmark: 30
        );
    }
}
```

## Error Messages and Debugging

### Custom Error Messages

Provide detailed failure information:

```php
public function test_routes_with_detailed_messages()
{
    $this->assertRoutesAreTested(
        ['critical.*'],
        message: function($failedRoutes) {
            $routes = $failedRoutes->pluck('identifier');
            $count = $routes->count();

            return "Critical security issue: {$count} routes lack test coverage:\n" .
                   $routes->map(fn($r) => "  - {$r}")->join("\n") .
                   "\n\nAdd tests before deploying to production.";
        }
    );
}
```

### Debugging Failed Assertions

Use conditional messages for troubleshooting:

```php
public function test_complex_security_requirements()
{
    $this->assertAuditRoutesOk(
        ['*'],
        [PolicyAuditor::make()],
        function($failedRoutes) {
            $details = $failedRoutes->map(function($route) {
                return sprintf(
                    '%s (Score: %d, Middleware: %s)',
                    $route->getIdentifier(),
                    $route->getScore(),
                    implode(', ', $route->getMiddleware())
                );
            });

            return "Policy authorization failures:\n" . $details->join("\n");
        },
        benchmark: 1
    );
}
```

## Best Practices

### Organize Security Tests
- Group security assertions in dedicated test classes
- Use descriptive test method names
- Include both positive and negative test cases

### Performance Considerations
- Use specific route patterns instead of `['*']` when possible
- Cache audit results in long-running test suites
- Consider running security tests in parallel with functional tests

### Maintenance
- Update ignored routes when adding development/debug routes
- Review security benchmarks regularly
- Keep custom error messages informative and actionable

## Next Steps

- **[Basic Usage Guide](../../guides/basic-usage.md)**: Learn practical audit patterns
- **[Commands API](commands.md)**: Understand command-line alternatives
- **[Custom Auditors](../../guides/custom-auditors.md)**: Build application-specific security checks
- **[CI Integration](../../guides/ci-integration.md)**: Automate security testing