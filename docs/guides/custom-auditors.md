# Building Custom Auditors Guide

Create application-specific security auditors to enforce your unique business requirements and security standards. This guide covers auditor development, testing, and integration patterns.

## Auditor Architecture

### Understanding the AuditorInterface

All auditors must implement the `AuditorInterface` contract:

```php
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;

interface AuditorInterface
{
    public function handle(RouteInterface $route): int;
    public function setWeight(int $weight): self;
    public function setPenalty(int $penalty): self;
    public function setArguments(?array $arguments): self;
}
```

### Using the Auditable Trait

The `Auditable` trait provides core functionality for scoring and configuration:

```php
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class CustomSecurityAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        // Your auditing logic here
        $complianceScore = $this->analyzeRoute($route);

        // Use the trait's getScore method for consistent scoring
        return $this->getScore($complianceScore);
    }

    private function analyzeRoute(RouteInterface $route): int
    {
        // Return 1 for compliant routes, 0 for non-compliant
        return $this->isCompliant($route) ? 1 : 0;
    }
}
```

## Simple Custom Auditors

### Rate Limiting Auditor

Ensure API routes have appropriate rate limiting middleware:

```php
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class RateLimitAuditor implements AuditorInterface
{
    use Auditable;

    private array $rateLimitPatterns = [
        'throttle:', 'rate_limit:', 'api_throttle:'
    ];

    public function handle(RouteInterface $route): int
    {
        // Only audit API routes
        if (!str_starts_with($route->getIdentifier(), 'api.')) {
            return $this->getScore(1); // Not applicable = neutral score
        }

        $middlewares = $route->getMiddlewares();

        foreach ($middlewares as $middleware) {
            foreach ($this->rateLimitPatterns as $pattern) {
                if (str_contains($middleware->getName(), $pattern)) {
                    return $this->getScore(1); // Has rate limiting
                }
            }
        }

        return $this->getScore(0); // Missing rate limiting
    }
}

// Usage
$result = AuditRoutes::for($routes)->run([
    RateLimitAuditor::make()
        ->setWeight(15)
        ->setPenalty(-25)
        ->setName('API Rate Limiting')
]);
```

### HTTPS Enforcement Auditor

Verify routes require HTTPS in production:

```php
class HTTPSAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        // Skip in non-production environments
        if (!app()->isProduction()) {
            return $this->getScore(1);
        }

        $middlewares = $route->getMiddlewares();

        // Check for HTTPS enforcement middleware
        $httpsMiddleware = [
            'force.ssl', 'require.https', 'ssl', 'https'
        ];

        foreach ($middlewares as $middleware) {
            if (in_array($middleware->getName(), $httpsMiddleware)) {
                return $this->getScore(1);
            }
        }

        // Check if route explicitly allows HTTP
        if ($this->allowsHttp($route)) {
            return $this->getScore(0);
        }

        return $this->getScore(1); // Default secure assumption
    }

    private function allowsHttp(RouteInterface $route): bool
    {
        // Check route configuration or annotations
        return str_contains($route->getUri(), 'webhook') ||
               str_contains($route->getIdentifier(), 'api.public.');
    }
}
```

## Advanced Custom Auditors

### Business Rule Auditor

Enforce complex business logic requirements:

```php
class BusinessRuleAuditor implements AuditorInterface
{
    use Auditable;

    private array $financialRoutes = [
        'payments.', 'billing.', 'invoices.', 'transactions.'
    ];

    private array $sensitiveDataRoutes = [
        'users.personal', 'profiles.private', 'documents.confidential'
    ];

    public function handle(RouteInterface $route): int
    {
        $routeName = $route->getIdentifier();
        $score = 0;

        // Financial routes require strict security
        if ($this->isFinancialRoute($routeName)) {
            $score = $this->auditFinancialRoute($route);
        }
        // Sensitive data routes need data protection
        elseif ($this->isSensitiveDataRoute($routeName)) {
            $score = $this->auditSensitiveDataRoute($route);
        }
        // Standard business routes
        else {
            $score = $this->auditStandardRoute($route);
        }

        return $this->getScore($score);
    }

    private function auditFinancialRoute(RouteInterface $route): int
    {
        $requirements = [
            'has_authentication' => $this->hasMiddleware($route, ['auth']),
            'has_2fa' => $this->hasMiddleware($route, ['2fa', 'two-factor']),
            'has_audit_logging' => $this->hasMiddleware($route, ['audit-log']),
            'has_rate_limiting' => $this->hasRateLimit($route),
            'has_csrf_protection' => $this->hasCSRFProtection($route),
        ];

        $passed = array_filter($requirements);
        $total = count($requirements);

        // Financial routes must pass all checks
        if (count($passed) === $total) {
            return 2; // Excellent compliance
        } elseif (count($passed) >= $total * 0.8) {
            return 1; // Good compliance
        } else {
            return -1; // Insufficient security
        }
    }

    private function auditSensitiveDataRoute(RouteInterface $route): int
    {
        $score = 0;

        // Must have authentication
        if (!$this->hasMiddleware($route, ['auth'])) {
            return -2; // Critical failure
        }
        $score++;

        // Should have authorization
        if ($this->hasMiddleware($route, ['can:', 'authorize'])) {
            $score++;
        }

        // Should have data encryption middleware
        if ($this->hasMiddleware($route, ['encrypt-response'])) {
            $score++;
        }

        return $score;
    }
}
```

### Compliance Auditor

Check compliance with security standards (OWASP, PCI DSS, etc.):

```php
class ComplianceAuditor implements AuditorInterface
{
    use Auditable;

    private string $standard;
    private array $requirements;

    public function __construct(string $standard = 'owasp')
    {
        $this->standard = $standard;
        $this->requirements = $this->getRequirementsForStandard($standard);
    }

    public function handle(RouteInterface $route): int
    {
        $complianceScore = 0;
        $totalRequirements = count($this->requirements);

        foreach ($this->requirements as $requirement => $checker) {
            if ($this->$checker($route)) {
                $complianceScore++;
            }
        }

        // Calculate compliance percentage
        $percentage = ($complianceScore / $totalRequirements) * 100;

        return $this->getScore($this->scoreFromPercentage($percentage));
    }

    private function getRequirementsForStandard(string $standard): array
    {
        return match($standard) {
            'owasp' => [
                'authentication' => 'checkAuthentication',
                'authorization' => 'checkAuthorization',
                'input_validation' => 'checkInputValidation',
                'csrf_protection' => 'checkCSRFProtection',
                'secure_headers' => 'checkSecureHeaders',
            ],
            'pci_dss' => [
                'authentication' => 'checkAuthentication',
                'encryption' => 'checkEncryption',
                'access_control' => 'checkAccessControl',
                'audit_logging' => 'checkAuditLogging',
            ],
            default => []
        };
    }

    private function checkAuthentication(RouteInterface $route): bool
    {
        return $this->hasMiddleware($route, ['auth', 'auth:sanctum']);
    }

    private function checkInputValidation(RouteInterface $route): bool
    {
        // Check if route has validation middleware or form requests
        return $this->hasMiddleware($route, ['validate', 'sanitize']) ||
               $this->usesFormRequest($route);
    }

    private function scoreFromPercentage(float $percentage): int
    {
        return match(true) {
            $percentage >= 90 => 2,  // Excellent compliance
            $percentage >= 75 => 1,  // Good compliance
            $percentage >= 50 => 0,  // Partial compliance
            default => -1            // Poor compliance
        };
    }
}
```

## Configurable Auditors

### Parameterized Security Auditor

Create auditors that accept configuration parameters:

```php
class ConfigurableSecurityAuditor implements AuditorInterface
{
    use Auditable;

    private array $requiredMiddleware = [];
    private array $forbiddenMiddleware = [];
    private array $conditionalRules = [];
    private int $minimumScore = 0;

    public function setArguments(?array $arguments): self
    {
        $config = $arguments[0] ?? [];

        $this->requiredMiddleware = $config['required_middleware'] ?? [];
        $this->forbiddenMiddleware = $config['forbidden_middleware'] ?? [];
        $this->conditionalRules = $config['conditional_rules'] ?? [];
        $this->minimumScore = $config['minimum_score'] ?? 0;

        return $this;
    }

    public function handle(RouteInterface $route): int
    {
        $score = 0;
        $routeName = $route->getIdentifier();

        // Check required middleware
        foreach ($this->requiredMiddleware as $middleware) {
            if ($this->hasMiddleware($route, [$middleware])) {
                $score++;
            } else {
                $score--;
            }
        }

        // Check forbidden middleware
        foreach ($this->forbiddenMiddleware as $middleware) {
            if ($this->hasMiddleware($route, [$middleware])) {
                $score -= 2; // Penalty for forbidden middleware
            }
        }

        // Apply conditional rules
        foreach ($this->conditionalRules as $rule) {
            if ($this->matchesCondition($route, $rule['condition'])) {
                $score += $this->evaluateRule($route, $rule);
            }
        }

        // Enforce minimum score
        if ($score < $this->minimumScore) {
            $score = $this->minimumScore;
        }

        return $this->getScore($score);
    }

    private function matchesCondition(RouteInterface $route, array $condition): bool
    {
        $routeName = $route->getIdentifier();

        return match($condition['type']) {
            'route_prefix' => str_starts_with($routeName, $condition['value']),
            'route_suffix' => str_ends_with($routeName, $condition['value']),
            'route_contains' => str_contains($routeName, $condition['value']),
            'http_method' => in_array($route->getMethod(), $condition['value']),
            default => false
        };
    }
}

// Usage with complex configuration
$securityConfig = [
    'required_middleware' => ['auth', 'verified'],
    'forbidden_middleware' => ['guest'],
    'conditional_rules' => [
        [
            'condition' => ['type' => 'route_prefix', 'value' => 'admin.'],
            'required_middleware' => ['role:admin'],
            'score_modifier' => 2
        ],
        [
            'condition' => ['type' => 'http_method', 'value' => ['POST', 'PUT', 'DELETE']],
            'required_middleware' => ['csrf'],
            'score_modifier' => 1
        ]
    ],
    'minimum_score' => -5
];

$auditor = ConfigurableSecurityAuditor::make()
    ->setArguments([$securityConfig])
    ->setWeight(25);
```

## Testing Custom Auditors

### Unit Testing Auditors

Create comprehensive tests for your custom auditors:

```php
use MyDev\AuditRoutes\Tests\TestCase;
use MyDev\AuditRoutes\Entities\Route;
use MyDev\AuditRoutes\Entities\Middleware;

class RateLimitAuditorTest extends TestCase
{
    private RateLimitAuditor $auditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditor = new RateLimitAuditor();
        $this->auditor->setWeight(10);
    }

    public function test_api_route_with_throttle_passes()
    {
        $route = $this->createMockRoute('api.users.index', [
            new Middleware('throttle:60,1')
        ]);

        $score = $this->auditor->handle($route);

        $this->assertEquals(10, $score);
    }

    public function test_api_route_without_throttle_fails()
    {
        $route = $this->createMockRoute('api.users.index', [
            new Middleware('auth:sanctum')
        ]);

        $this->auditor->setPenalty(-20);
        $score = $this->auditor->handle($route);

        $this->assertEquals(-20, $score);
    }

    public function test_non_api_route_is_neutral()
    {
        $route = $this->createMockRoute('web.dashboard', []);

        $score = $this->auditor->handle($route);

        $this->assertEquals(10, $score); // Weight applied to neutral score
    }

    private function createMockRoute(string $name, array $middlewares): RouteInterface
    {
        $route = $this->createMock(RouteInterface::class);
        $route->method('getIdentifier')->willReturn($name);
        $route->method('getMiddlewares')->willReturn($middlewares);

        return $route;
    }
}
```

### Integration Testing

Test auditors within the full audit system:

```php
class CustomAuditorIntegrationTest extends TestCase
{
    public function test_custom_auditor_in_audit_system()
    {
        $routes = $this->getTestRoutes();

        $result = AuditRoutes::for($routes)
            ->setBenchmark(25)
            ->run([
                RateLimitAuditor::make()
                    ->setWeight(20)
                    ->setPenalty(-30),
                HTTPSAuditor::make()
                    ->setWeight(15)
            ]);

        $this->assertGreaterThan(0, $result->count());

        // Test specific route scores
        $apiRoute = $result->getRouteByIdentifier('api.users.index');
        $this->assertNotNull($apiRoute);
        $this->assertGreaterThan(0, $apiRoute->getScore());
    }
}
```

## Advanced Auditor Patterns

### Composite Auditor

Combine multiple auditors into a single comprehensive auditor:

```php
class CompositeSecurityAuditor implements AuditorInterface
{
    use Auditable;

    private array $auditors = [];
    private array $weights = [];

    public function addAuditor(AuditorInterface $auditor, int $weight = 1): self
    {
        $this->auditors[] = $auditor;
        $this->weights[] = $weight;

        return $this;
    }

    public function handle(RouteInterface $route): int
    {
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($this->auditors as $index => $auditor) {
            $score = $auditor->handle($route);
            $weight = $this->weights[$index];

            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        $averageScore = $totalWeight > 0 ? $totalScore / $totalWeight : 0;

        return $this->getScore($averageScore);
    }
}

// Usage
$compositeAuditor = CompositeSecurityAuditor::make()
    ->addAuditor(new RateLimitAuditor(), 2)
    ->addAuditor(new HTTPSAuditor(), 1)
    ->addAuditor(new BusinessRuleAuditor(), 3);
```

### Conditional Auditor

Apply different auditing logic based on route characteristics:

```php
class ConditionalAuditor implements AuditorInterface
{
    use Auditable;

    private array $conditions = [];

    public function addCondition(Closure $condition, AuditorInterface $auditor): self
    {
        $this->conditions[] = ['condition' => $condition, 'auditor' => $auditor];
        return $this;
    }

    public function handle(RouteInterface $route): int
    {
        foreach ($this->conditions as $rule) {
            if ($rule['condition']($route)) {
                return $rule['auditor']->handle($route);
            }
        }

        return $this->getScore(0); // No conditions matched
    }
}

// Usage
$conditionalAuditor = ConditionalAuditor::make()
    ->addCondition(
        fn($route) => str_starts_with($route->getIdentifier(), 'api.'),
        new RateLimitAuditor()
    )
    ->addCondition(
        fn($route) => str_starts_with($route->getIdentifier(), 'admin.'),
        new BusinessRuleAuditor()
    );
```

## Best Practices

### Auditor Design Principles

1. **Single Responsibility**: Each auditor should check one specific security aspect
2. **Configurable**: Use `setArguments()` to make auditors reusable
3. **Testable**: Design auditors with clear input/output for easy testing
4. **Performance**: Avoid expensive operations in the `handle()` method
5. **Consistent Scoring**: Use the `Auditable` trait for standardized scoring

### Error Handling

```php
class RobustAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        try {
            return $this->performAudit($route);
        } catch (Exception $e) {
            // Log error but don't break the audit process
            Log::warning('Auditor error', [
                'auditor' => static::class,
                'route' => $route->getIdentifier(),
                'error' => $e->getMessage()
            ]);

            // Return neutral score on error
            return $this->getScore(0);
        }
    }
}
```

### Documentation

Document your custom auditors thoroughly:

```php
/**
 * Rate Limit Auditor
 *
 * Ensures API routes have appropriate rate limiting middleware to prevent abuse.
 *
 * Scoring:
 * - API routes with rate limiting: +1 (multiplied by weight)
 * - API routes without rate limiting: 0 (multiplied by penalty if set)
 * - Non-API routes: +1 (neutral/not applicable)
 *
 * Configuration:
 * - No arguments required
 * - Detects common rate limiting patterns: 'throttle:', 'rate_limit:', 'api_throttle:'
 *
 * @example
 * RateLimitAuditor::make()->setWeight(15)->setPenalty(-25)
 */
class RateLimitAuditor implements AuditorInterface
{
    // Implementation...
}
```

## Next Steps

- **[Testing Guide](testing.md)**: Test your custom auditors with PHPUnit assertions
- **[Advanced Usage Guide](advanced-usage.md)**: Complex auditor configurations and enterprise patterns
- **[CI Integration Guide](ci-integration.md)**: Automate custom auditor deployment
- **[Configuration Guide](../getting-started/configuration.md)**: Configure auditor settings and behavior
- **[Architecture Overview](../reference/architecture/overview.md)**: Understand the auditor system internals
- **[API Reference](../reference/api/auditors.md)**: Core auditor class documentation