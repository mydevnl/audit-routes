# Auditor Classes API Reference

Auditors are the core components that analyze your Laravel routes for security vulnerabilities and compliance issues. Each auditor focuses on a specific aspect of route security and returns a score based on the configured weight and penalty values.

## Core Auditor Classes

### PhpUnitAuditor

Analyzes test coverage for routes by parsing PHPUnit test files and identifying which routes are tested.

**Purpose**: Ensures all routes have corresponding test coverage to prevent regression issues.

**Class**: `MyDev\AuditRoutes\Auditors\PhpUnitAuditor`

**Configuration**:
```php
PhpUnitAuditor::make()
    ->setWeight(10)          // Positive weight per testcase for routes
    ->setPenalty(-100)       // Penalty for untested routes
    ->setLimit(100)          // Maximum score bonus
    ->setArguments([         // Optional test method filters
        fn(ClassMethod $method) => str_contains($method->name->toString(), 'test_')
    ])
```

**Example Usage**:
```php
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;

AuditRoutes::for($routes)->run([
    PhpUnitAuditor::make()
        ->setWeight(5)
        ->setPenalty(-50)
]);
```

**Returns**: Score based on number of test methods that reference the route.

---

### PolicyAuditor

Checks for Laravel policy-based authorization middleware on routes.

**Purpose**: Ensures routes implement proper authorization through Laravel policies.

**Class**: `MyDev\AuditRoutes\Auditors\PolicyAuditor`







**Detection Logic**: Identifies routes with `can` middleware that include policy references (more than one attribute).

**Configuration**:
```php
PolicyAuditor::make()
    ->setWeight(15)    // Score boost for policy-protected routes
    ->setPenalty(-20)  // Penalty for routes lacking policies
```

**Example Usage**:
```php
// Routes with policy protection
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->middleware('can:view,post');

// Audit configuration
AuditRoutes::for($routes)->run([
    PolicyAuditor::class => 20  // Simple weight assignment
]);
```

**Returns**: Positive score for routes with policy middleware, zero for routes without.

---

### PermissionAuditor

Detects routes protected by permission-based authorization middleware.

**Purpose**: Validates that routes implement permission checks for role-based access control.

**Class**: `MyDev\AuditRoutes\Auditors\PermissionAuditor`

**Detection Logic**: Identifies routes with `can` middleware containing exactly one attribute (permission name).

**Configuration**:
```php
PermissionAuditor::make()
    ->setWeight(10)
    ->setPenalty(-15)
```

**Example Usage**:
```php
// Routes with permission protection
Route::post('/admin/users', [AdminController::class, 'createUser'])
    ->middleware('can:create-users');

// Audit configuration
AuditRoutes::for($routes)->run([
    PermissionAuditor::class => -10  // Negative weight for missing permissions
]);
```

**Returns**: Score based on presence of permission middleware.

---

### MiddlewareAuditor

Validates that routes implement specified middleware for security compliance.

**Purpose**: Ensures routes have essential security middleware like authentication, throttling, or custom protection.

**Class**: `MyDev\AuditRoutes\Auditors\MiddlewareAuditor`

**Configuration**:
```php
MiddlewareAuditor::make()
    ->setWeight(5)
    ->setArguments([
        'auth',                   // Authentication middleware
        'throttle:api',           // Rate limiting
        'verified',               // Email verification
        MyCustomMiddleware::class // Custom middleware class
    ])
```

**Example Usage**:
```php
// Routes with required middleware
Route::apiResource('orders', OrderController::class)
    ->middleware(['auth:sanctum', 'throttle:api']);

// Audit for specific middleware
AuditRoutes::for($routes)->run([
    MiddlewareAuditor::make()
        ->setWeight(10)
        ->setArguments(['auth', 'throttle'])
]);
```

**Returns**: Score based on number of matched middleware instances.

---

### ScopedBindingAuditor

Checks for proper Laravel route model binding scoping on nested resource routes.

**Purpose**: Prevents unauthorized access to nested resources by ensuring parent-child relationships are enforced.

**Class**: `MyDev\AuditRoutes\Auditors\ScopedBindingAuditor`

**Scoring System**:
- `OK (2)`: Route has proper scoped bindings
- `NOT_APPLICABLE (1)`: Route doesn't need scoping (single parameter)
- `FAIL (0)`: Route has multiple parameters but lacks scoped binding

**Configuration**:
```php
ScopedBindingAuditor::make()
    ->setWeight(25)    // High weight for security-critical feature
    ->setPenalty(-50)  // Significant penalty for missing scoping
```

**Example Usage**:
```php
// Properly scoped route
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show'])
    ->scopeBindings();

// Audit configuration
AuditRoutes::for($routes)->run([
    ScopedBindingAuditor::class => 25
]);
```

**Returns**: Score indicating scoped binding compliance level.

## Auditor Interface

All auditors implement the `AuditorInterface` contract:

```php
interface AuditorInterface
{
    public function handle(RouteInterface $route): int;
    public function setWeight(int $weight): self;
    public function setPenalty(int $penalty): self;
    public function setArguments(?array $arguments): self;
}
```

## Common Methods

### Scoring Methods
- `setWeight(int $weight)`: Set positive score for compliant routes
- `setPenalty(int $penalty)`: Set negative score for non-compliant routes
- `setLimit(int $limit)`: Maximum score cap (PhpUnitAuditor only)

### Factory Methods
- `make()`: Create new auditor instance with default configuration
- `setArguments(array $args)`: Configure auditor-specific parameters

## Custom Auditor Development

To create custom auditors, implement the `AuditorInterface`:

```php
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class CustomSecurityAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        // Your custom analysis logic
        $hasCustomSecurity = $this->checkCustomSecurity($route);

        return $this->getScore($hasCustomSecurity ? 1 : 0);
    }
}
```

## Next Steps

- **[Commands API](commands.md)**: Learn about available Artisan commands
- **[Custom Auditors Guide](../../guides/custom-auditors.md)**: Build application-specific auditors
- **[Advanced Usage](../../guides/advanced-usage.md)**: Complex auditor configurations
- **[Basic Usage Guide](../../guides/basic-usage.md)**: Practical auditor examples