# Auditor System Architecture

Deep dive into how Audit Routes' auditor system works internally, covering the actual execution flow, scoring mechanics, and extension patterns based on the real implementation.

## Auditor Factory System

### AuditorFactory Implementation

**Location**: `src/Auditors/AuditorFactory.php`

The factory handles multiple input formats and creates properly configured auditors.

**Supported Input Formats**:
```php
// Pre-configured instance
PolicyAuditor::make()->setWeight(25)->when(fn($r) => str_contains($r->getIdentifier(), 'admin'))

// Class string with weight
['PolicyAuditor' => 25, 'MiddlewareAuditor' => 30]

// Simple class strings
['PolicyAuditor', 'MiddlewareAuditor']

// Mixed formats
[
    'PolicyAuditor' => 25,
    MiddlewareAuditor::make(['auth'])->setWeight(20),
    'PhpUnitAuditor'
]
```
## Built-in Auditor Implementations

### MiddlewareAuditor

**Location**: `src/Auditors/MiddlewareAuditor.php`

Validates presence of specific middleware:

**Usage**:
```php
// Check for authentication middleware
MiddlewareAuditor::make(['auth'])->setWeight(25)

// Check for multiple middleware
MiddlewareAuditor::make(['auth', 'verified', 'throttle:60,1'])->setWeight(30)
```

### PolicyAuditor

**Location**: `src/Auditors/PolicyAuditor.php`

Detects Laravel policy middleware with multiple attributes.

**Detection Logic**:
- Looks for the class-string `Illuminate\Auth\Middleware\Authorize` or the alias `can`
- Requires 2+ attributes to distinguish from simple permissions
- Examples: `can:view,post`, `can:update,user`

### PermissionAuditor

**Location**: `src/Auditors/PermissionAuditor.php`

Detects permission-based authorization with single attributes.

**Detection Logic**:
- Looks for the class-string `Illuminate\Auth\Middleware\Authorize` or the alias `can`
- Looks for `can:permission` middleware patterns
- Requires exactly 1 attribute to distinguish from policies
- Examples: `can:create-posts`, `can:manage-users`

### ScopedBindingAuditor

**Location**: `src/Auditors/ScopedBindingAuditor.php`

Validates route model binding security for nested resources.

**Scoring Logic**:
- **2 points**: Route has proper scoped bindings (e.g., `->scopeBindings()`)
- **1 point**: Route doesn't need scoping (single parameter)
- **0 points**: Route has multiple parameters but no scoped bindings (security risk)

### PhpUnitAuditor

**Location**: `src/Auditors/PhpUnitAuditor.php`

Complex auditor using AST parsing for test coverage analysis.

## Scoring System

**Scoring Properties**:
- `$weight = 1`: Multiplier for each testcase
- `$penalty = 0`: Score for zero/failed results
- `$limit = PHP_INT_MAX`: Maximum score cap

**Examples**:
```php
// Basic scoring
$auditor = PolicyAuditor::make()->setWeight(25);
$auditor->getScore(1); // Returns 25 (1 * 25)
$auditor->getScore(0); // Returns 0 (penalty)

// With penalty
$auditor = PolicyAuditor::make()->setWeight(25)->setPenalty(-10);
$auditor->getScore(0); // Returns -10 (penalty)
$auditor->getScore(2); // Returns 50 (2 * 25)

// With limit
$auditor = PhpUnitAuditor::make()->setWeight(10)->setLimit(50);
$auditor->getScore(3); // Returns 30 (3 * 10)
$auditor->getScore(10); // Returns 50 (limited)
```

### Status Determination

Routes are categorized based on total score vs benchmark.

**Status Categories**:
- **Passed**: Score >= benchmark (security requirements met)
- **Failed**: Score < benchmark

## Filtering System

### Route Filtering (IgnoresRoutes Trait)

**Location**: `src/Traits/IgnoresRoutes.php`

### Conditional Filtering (ConditionalAuditable Trait)

**Location**: `src/Traits/ConditionalAuditable.php`

**Usage Examples**:
```php
// Ignore API routes for all auditors
AuditRoutes::for($routes)
    ->ignoreRoutes(['api.*'])
    ->run([])

// Apply auditor specifically to admin routes
PolicyAuditor::make()
    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.'))

// Apply auditor specifically to API routes, excluding health checks
MiddlewareAuditor::make(['auth:sanctum'])
    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'api.'))
    ->ignoreRoutes(['api.health', 'api.status'])

// Complex conditions
PhpUnitAuditor::make()
    ->when(fn($route) => str_contains($route->getUri(), '/admin/'))
    ->when(fn($route) => !str_contains($route->getUri(), '/auth/'))
    ->ignoreRoutes(['*/login'])
```

## Middleware Analysis System

### Middleware Entity

**Location**: `src/Entities/Middleware.php`

Represents middleware with attribute parsing.

**Middleware Parsing Examples**:
```php
Middleware::from('auth');
// resolver: 'auth', alias: 'auth', attributes: []

Middleware::from('throttle:60,1');
// resolver: 'throttle', alias: 'throttle', attributes: ['60', '1']

Middleware::from('can:view,post');
// resolver: 'can', alias: 'can', attributes: ['view', 'post']
```

**Middleware Comparison Examples**:
```php
$a = Middleware::from('auth');
$b = Middleware::from('auth:sanctum');

$a->is($b);
// false: 'auth' is not 'auth:sanctum' because auth:sanctum is more specific

$b->is($a);
// true: 'auth:sanctum' is 'auth' because auth is less specific
```

## Custom Auditor Development

### Basic Custom Auditor

```php
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class CSRFAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        // Check if route has CSRF protection
        $hasCSRF = collect($route->getMiddlewares())
            ->contains(fn($middleware) => $middleware->is('web', 'VerifyCsrfToken'));

        return $hasCSRF ? 1 : 0;
    }

    // Optional: Custom validation method (called automatically)
    protected function validateHttpMethod(RouteInterface $route): bool
    {
        // Only check POST/PUT/PATCH routes
        return in_array($route->getMethod(), ['POST', 'PUT', 'PATCH']);
    }
}
```

### Auditor with Complex Validation

```php
class SecurityHeadersAuditor implements AuditorInterface
{
    use Auditable;

    protected array $requiredHeaders = [
        'X-Frame-Options',
        'X-Content-Type-Options',
        'X-XSS-Protection'
    ];

    public function handle(RouteInterface $route): int
    {
        $securityMiddleware = collect($route->getMiddlewares())
            ->filter(fn($middleware) => $this->isSecurityMiddleware($middleware))
            ->count();

        return $securityMiddleware;
    }

    protected function validateSecureRoutes(RouteInterface $route): bool
    {
        // Only audit HTTPS routes
        return str_starts_with($route->getUri(), 'https://') ||
               !str_contains($route->getUri(), '://');
    }

    protected function validatePublicRoutes(RouteInterface $route): bool
    {
        // Skip internal/debugging routes
        $internalPrefixes = ['_debugbar', '_ignition', 'telescope'];

        foreach ($internalPrefixes as $prefix) {
            if (str_starts_with($route->getIdentifier(), $prefix)) {
                return false;
            }
        }

        return true;
    }

    private function isSecurityMiddleware(Middleware $middleware): bool
    {
        return $middleware->is('security.headers', 'SecureHeaders') ||
               str_contains($middleware->getResolver(), 'Security');
    }
}
```

## Performance Optimization

### Auditor Caching

```php
class CachedAuditor implements AuditorInterface
{
    use Auditable;

    private static array $cache = [];

    public function handle(RouteInterface $route): int
    {
        $cacheKey = $this->getCacheKey($route);

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $result = $this->performAnalysis($route);
        self::$cache[$cacheKey] = $result;

        return $result;
    }

    private function getCacheKey(RouteInterface $route): string
    {
        return md5($route->getIdentifier() . serialize($route->getMiddlewares()));
    }
}
```

### Lazy Evaluation

```php
class LazyAuditor implements AuditorInterface
{
    use Auditable;

    private ?array $expensiveData = null;

    public function handle(RouteInterface $route): int
    {
        // Only load expensive data if needed
        if ($this->routeNeedsExpensiveCheck($route)) {
            $this->ensureDataLoaded();
            return $this->performExpensiveAnalysis($route);
        }

        return $this->performQuickAnalysis($route);
    }

    private function ensureDataLoaded(): void
    {
        if ($this->expensiveData === null) {
            $this->expensiveData = $this->loadExpensiveData();
        }
    }
}
```

## Integration with AuditRoutes Core

### Full Integration Example

```php
// How auditors integrate with the main system
$result = AuditRoutes::for($routes)
    ->setBenchmark(50)
    ->run([
        // Factory handles instantiation
        PolicyAuditor::make()->setWeight(30),
        MiddlewareAuditor::make(['auth'])->setWeight(25),
        PhpUnitAuditor::make()->setWeight(20)->setLimit(100),

        // Conditional application
        CustomAuditor::make()
            ->setWeight(15)
            ->when(fn($route) => str_starts_with($route->getIdentifier(), 'secure.'))
            ->ignoreRoutes(['secure.public.*']),
    ]);

// Process results
foreach ($result->getRoutes() as $auditedRoute) {
    echo sprintf(
        "Route: %s, Score: %d, Status: %s\n",
        $auditedRoute->getIdentifier(),
        $auditedRoute->getScore(),
        $auditedRoute->getStatus()->value
    );
}
```

## Next Steps

- **[Architecture Overview](overview.md)**: Understanding the complete system architecture
- **[Custom Auditors Guide](../guides/custom-auditors.md)**: Practical auditor development examples
- **[Advanced Usage](../guides/advanced-usage.md)**: Complex auditor configurations and patterns