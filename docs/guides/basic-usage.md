# Basic Usage Guide

Learn essential patterns and practical examples for auditing your Laravel routes. This guide covers the most common security scenarios and how to address them with Audit Routes.

## Quick Security Assessment

### Identify Unprotected Routes

Start with a comprehensive audit to get an overview of your application's security status:

```bash
php artisan route:audit --benchmark 25 -vv
```

**What this reveals**:
- Routes scoring below 25 points need immediate attention
- Negative scores indicate security vulnerabilities
- Zero scores suggest missing protection measures

**Example output interpretation**:
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        admin.users.destroy                  -50
  ✖        api.orders.index                     24
  ✓        users.profile.edit                   75
 -------- ------------------------------------ -------- 

[ERROR] 2/3 routes scored below the benchmark
```

### Focus on Critical Issues

Generate an HTML report for stakeholder review:

```bash
php artisan route:audit-report
```

This creates a comprehensive dashboard at `storage/exports/audit-routes/index.html` showing:
- Executive summary with key metrics
- Detailed route-by-route analysis
- Trends and security coverage statistics

## Common Security Patterns

### Authentication Verification

Check which routes lack authentication requirements:

```bash
php artisan route:audit-auth --export html --filename auth.html -vv
```

**Interpreting results**:
- **Guest routes**: Intentionally public (welcome, login, register)
- **Unprotected routes**: Accidentally public (security risk)
- **API routes**: Should use `auth:sanctum` or similar token auth

**Common fixes**:
```php
// Add authentication to route groups
Route::middleware(['auth'])->group(function () {
    Route::resource('orders', OrderController::class);
    Route::get('profile', [ProfileController::class, 'show']);
});

// API authentication
Route::middleware(['auth:sanctum'])->prefix('api')->group(function () {
    Route::apiResource('users', UserController::class);
});
```

### Test Coverage Analysis

Identify routes without automated test coverage:

```bash
php artisan route:audit-test-coverage --benchmark 1 -vv
```

**Results show**:
- **Covered routes**: Have corresponding test methods
- **Uncovered routes**: Lack automated testing
- **Coverage count**: Number of tests per route

**Adding missing tests**:
```php
// Feature test example
public function test_user_can_view_orders()
{
    $user = User::factory()->create();

    $this->actingAs($user)
         ->get(route('orders.index'))
         ->assertStatus(200);
}

public function test_guest_cannot_view_orders()
{
    $this->get(route('orders.index'))
         ->assertRedirect(route('login'));
}
```

## Programmatic Usage

### Custom Audit Scripts

Create tailored audits for specific security requirements:

```php
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;

// High-security audit for admin routes
$result = AuditRoutes::for($router->getRoutes())
    ->setBenchmark(75)  // Strict security standard
    ->run([
        // Apply strict policy auditing only to admin routes
        PolicyAuditor::make()
            ->setWeight(30)
            ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.')),

        // Test coverage mandatory for admin routes
        PhpUnitAuditor::make()
            ->setWeight(25)
            ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.')),

        // Multi-factor auth required for admin routes
        MiddlewareAuditor::make(['auth', 'verified'])
            ->setWeight(20)
            ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.'))
    ]);

// Process results
foreach ($result->getRoutes() as $route) {
    if ($route->getScore() < 75) {
        Log::warning("Admin route security violation", [
            'route' => $route->getIdentifier(),
            'score' => $route->getScore()
        ]);
    }
}
```

### Environment-Specific Audits

Different security standards for different environments:

```php
// config/audit-routes.php
return [
    'benchmark' => env('AUDIT_BENCHMARK', 0),
    'ignored-routes' => env('APP_ENV') === 'local' ? [
        'telescope*', 'debugbar.*', 'ignition.*'
    ] : [],
];
```

**Usage in different environments**:
```bash
# Development - relaxed standards
APP_ENV=local AUDIT_BENCHMARK=25 php artisan route:audit

# Staging - moderate standards
APP_ENV=staging AUDIT_BENCHMARK=50 php artisan route:audit

# Production - strict standards
APP_ENV=production AUDIT_BENCHMARK=75 php artisan route:audit
```

## Fixing Common Issues

### Missing Authentication

**Problem**: Routes accessible to unauthenticated users
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        orders.create                        -25
```

**Solution**: Add authentication middleware
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/orders/create', [OrderController::class, 'create']);
});
```

### Missing Authorization

**Problem**: Authenticated users can access any resource
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        orders.show                          25
```

**Solution**: Implement Laravel policies
```php
// Create policy
php artisan make:policy OrderPolicy --model=Order

// Apply policy to routes
Route::get('/orders/{order}', [OrderController::class, 'show'])
    ->middleware(['auth', 'can:view,order']);
```

### Missing Test Coverage

**Problem**: Routes lack automated test coverage
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        orders.store                         25
```

**Solution**: Add comprehensive tests
```php
public function test_authenticated_user_can_create_order()
{
    $user = User::factory()->create();
    $orderData = ['product_id' => 1, 'quantity' => 2];

    $this->actingAs($user)
         ->post(route('orders.store'), $orderData)
         ->assertStatus(201);
}

public function test_guest_cannot_create_order()
{
    $this->post(route('orders.store'), [])
         ->assertRedirect(route('login'));
}
```

### Improper Scoped Bindings

**Problem**: Nested resources not properly scoped
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        users.posts.show                     -25
```

**Solution**: Enable scoped bindings
```php
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show'])
    ->scopeBindings();  // Ensures post belongs to user
```

## Monitoring and Maintenance

### Regular Security Checks

Set up automated monitoring:

```bash
#!/bin/bash
# weekly-security-check.sh

php artisan route:audit --benchmark 50 --export json --filename weekly-audit.json

FAILED=$(jq '.summary.failed' weekly-audit.json)
if [ "$FAILED" -gt 0 ]; then
    echo "⚠️ Weekly security audit: $FAILED routes need attention"
    # Send notification or create issue
fi
```

### Pre-deployment Validation

Ensure code changes don't introduce security regressions:

```bash
# In CI/CD pipeline
php artisan route:audit --benchmark 25
if [ $? -ne 0 ]; then
    echo "❌ Security audit failed - blocking deployment"
    exit 1
fi
```

### Team Reporting

Generate regular reports for security reviews:

```php
// Generate monthly security report
Artisan::call('route:audit-report');

// Email stakeholders
Mail::to('security@company.com')->send(
    new SecurityAuditReport(storage_path('exports/audit-routes'))
);
```

## Built-in Route Filtering

Audit Routes provides powerful built-in filtering features to focus audits on specific routes without manually filtering collections.

### Using `when()` for Conditional Auditing

Apply auditors only to routes that match specific conditions:

```php
$result = AuditRoutes::for($routes)->run([
    // Only audit API routes for Sanctum authentication
    MiddlewareAuditor::make(['auth:sanctum'])
        ->setWeight(30)
        ->when(fn($route) => str_starts_with($route->getIdentifier(), 'api.')),

    // Only check policies on admin routes
    PolicyAuditor::make()
        ->setWeight(25)
        ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.')),

    // Test coverage required for critical routes
    PhpUnitAuditor::make()
        ->setWeight(20)
        ->when(fn($route) => in_array($route->getIdentifier(), [
            'billing.process', 'payments.store', 'user.delete'
        ])),
]);
```

### Using `ignoreRoutes()` for Exclusions

Exclude specific routes or patterns from auditing:

```php
$result = AuditRoutes::for($routes)
    ->setBenchmark(50)
    ->run([
        // Ignore development and public routes
        PolicyAuditor::make()
            ->setWeight(30)
            ->ignoreRoutes(['login', 'register', 'password.*', 'telescope*']),

        // Skip middleware check on public routes
        MiddlewareAuditor::make(['auth'])
            ->setWeight(25)
            ->ignoreRoutes(['welcome', 'about', 'contact']),
    ]);
```

### Combining Filters for Complex Logic

```php
$result = AuditRoutes::for($routes)->run([
    // Admin routes need strict security, excluding public admin pages
    PolicyAuditor::make()
        ->setWeight(40)
        ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.'))
        ->ignoreRoutes(['admin.login', 'admin.forgot-password']),

    // API routes need authentication, excluding public API endpoints
    MiddlewareAuditor::make(['auth:sanctum'])
        ->setWeight(35)
        ->when(fn($route) => str_starts_with($route->getIdentifier(), 'api.'))
        ->ignoreRoutes(['api.health', 'api.status', 'api.documentation']),
]);
```

## Integration Patterns

### Laravel Middleware Integration

Create custom middleware that uses audit results:

```php
class RouteSecurityMiddleware
{
    public function handle($request, Closure $next)
    {
        $route = $request->route();

        // Run quick audit on current route
        $score = AuditRoutes::for([$route])->run([
            PolicyAuditor::make(),
            MiddlewareAuditor::make(['auth'])
        ])->getRoutes()->first()->getScore();

        if ($score < 0) {
            Log::critical('Insecure route accessed', [
                'route' => $route->getName(),
                'score' => $score
            ]);
        }

        return $next($request);
    }
}
```

### Custom Artisan Commands

Build application-specific security commands:

```php
class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit {--strict}';

    public function handle()
    {
        $benchmark = $this->option('strict') ? 75 : 25;

        $result = AuditRoutes::for($this->router->getRoutes())
            ->setBenchmark($benchmark)
            ->run([
                PolicyAuditor::make()->setWeight(25),
                PhpUnitAuditor::make()->setWeight(25),
            ]);

        $this->table(['Route', 'Score', 'Status'],
            $result->getRoutes()->map(fn($route) => [
                $route->getIdentifier(),
                $route->getScore(),
                $route->getScore() >= $benchmark ? '✅' : '❌'
            ])
        );
    }
}
```

## Command Line Options

### Advanced Audit Command

Run comprehensive audits with detailed options:

```bash
# Custom benchmark with detailed output and HTML export
php artisan route:audit --benchmark 75 --export html --filename report.html -vv
```

**Available options:**
- `--benchmark`: Set minimum score threshold for compliance
- `--export`: Choose output format (html, json)
- `--filename`: Specify custom filename for exports
- `-vv`: Very verbose output showing detailed results

### Comprehensive Reports

Generate multi-faceted audit reports:

```bash
# Generate default comprehensive report
php artisan route:audit-report
```

This command creates a complete HTML dashboard combining multiple audit perspectives, offering an opinionated configuration that serves as both a practical tool and example of orchestrating multiple audits together.

### Test Coverage Analysis

Detailed test coverage auditing:

```bash
# Test coverage with custom benchmark and export
php artisan route:audit-test-coverage --benchmark 1 --export html --filename test.html -vv
```

This provides insights into:
- Routes covered by automated tests
- Average number of tests per route
- Routes lacking test coverage
- Test coverage statistics and trends

### Authentication Analysis

Focused authentication middleware auditing:

```bash
# Authentication audit with HTML export
php artisan route:audit-auth --export html --filename auth.html -vv
```

This helps identify:
- Routes requiring authentication
- Public routes (intentional and unintentional)
- Authentication middleware patterns
- API authentication coverage

## Next Steps

- **[Advanced Usage](advanced-usage.md)**: Complex auditor configurations and custom scoring
- **[Custom Auditors](custom-auditors.md)**: Build application-specific security checks
- **[CI Integration](ci-integration.md)**: Automate security audits in your deployment pipeline
- **[Troubleshooting](troubleshooting.md)**: Resolve common configuration and usage issues