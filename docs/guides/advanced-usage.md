# Advanced Usage Guide

Master complex audit configurations, custom scoring systems, and sophisticated security patterns with Audit Routes. This guide covers enterprise-level usage patterns and advanced integration techniques.

## Complex Auditor Configurations

### Multi-Layered Security Analysis

Implement comprehensive security audits with weighted scoring systems:

```php
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\Auditors\ScopedBindingAuditor;

// Enterprise security standards
$result = AuditRoutes::for($router->getRoutes())
    ->setBenchmark(75)  // High security threshold
    ->run([
        // Authentication (25% weight)
        MiddlewareAuditor::make(['auth'])
            ->setWeight(25)
            ->setPenalty(-50)
            ->setLimit(25)
            ->setName('Authentication Required'),

        // Authorization (30% weight)
        PolicyAuditor::make()
            ->setWeight(30)
            ->setPenalty(-60)
            ->setLimit(30)
            ->setName('Policy Authorization'),

        // Test Coverage (10% per weight per test, maximum 25% weight)
        PhpUnitAuditor::make()
            ->setWeight(10)
            ->setPenalty(-40)
            ->setLimit(25)
            ->setName('Test Coverage'),

        // Scoped Bindings (20% weight)
        ScopedBindingAuditor::make()
            ->setWeight(20)
            ->setPenalty(-30)
            ->setLimit(20)
            ->setName('Secure Model Binding'),
    ]);

// Process results by security level
foreach ($result->getRoutes() as $route) {
    $score = $route->getScore();

    if ($score < 0) {
        Log::critical('Critical security vulnerability', [
            'route' => $route->getIdentifier(),
            'score' => $score
        ]);
    } elseif ($score < 50) {
        Log::warning('Security compliance issue', [
            'route' => $route->getIdentifier(),
            'score' => $score
        ]);
    }
}
```

### Conditional Auditing with Filters

Apply different security standards based on route characteristics:

```php
// API routes require token authentication
$apiAuditor = MiddlewareAuditor::make(['auth:sanctum'])
    ->setWeight(50)
    ->setPenalty(-100)
    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'api.'))
    ->setName('API Token Authentication');

// Admin routes need enhanced security
$adminAuditor = MiddlewareAuditor::make(['auth', 'verified', 'role:admin'])
    ->setWeight(75)
    ->setPenalty(-150)
    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.'))
    ->setName('Admin Security Stack');

// Public routes should not require permissions
$publicPermissionAuditor = PermissionAuditor::make()
    ->setWeight(-25)  // Negative weight penalizes permission requirements
    ->when(fn($route) => in_array($route->getIdentifier(), [
        'welcome', 'about', 'contact', 'terms'
    ]))
    ->setName('Public Route Accessibility');

$result = AuditRoutes::for($routes)->run([
    $apiAuditor,
    $adminAuditor,
    $publicPermissionAuditor,
]);
```

### Environment-Specific Auditing

Adapt security requirements based on deployment environment:

```php
class EnvironmentAwareAuditor
{
    public static function getAuditorsForEnvironment(string $environment): array
    {
        $baseAuditors = [
            PolicyAuditor::make()->setWeight(20),
            PhpUnitAuditor::make()->setWeight(15),
        ];

        return match($environment) {
            'production' => [
                ...$baseAuditors,
                MiddlewareAuditor::make(['auth'])->setWeight(30)->setPenalty(-100),
                ScopedBindingAuditor::make()->setWeight(25)->setPenalty(-75),
            ],
            'staging' => [
                ...$baseAuditors,
                MiddlewareAuditor::make(['auth'])->setWeight(20)->setPenalty(-50),
            ],
            'development' => [
                ...$baseAuditors,
                MiddlewareAuditor::make(['auth'])->setWeight(10)->setPenalty(-25),
            ],
            default => $baseAuditors,
        };
    }

    public static function getBenchmarkForEnvironment(string $environment): int
    {
        return match($environment) {
            'production' => 75,
            'staging' => 50,
            'development' => 25,
            default => 0,
        };
    }
}

// Usage
$environment = app()->environment();
$auditors = EnvironmentAwareAuditor::getAuditorsForEnvironment($environment);
$benchmark = EnvironmentAwareAuditor::getBenchmarkForEnvironment($environment);

$result = AuditRoutes::for($routes)
    ->setBenchmark($benchmark)
    ->run($auditors);
```

## Advanced Route Filtering

### Pattern-Based Route Segmentation

Audit different route groups with tailored security requirements:

```php
class RouteSegmentAuditor
{
    private Router $router;

    public function auditBySegments(): array
    {
        $segments = [
            'public' => ['welcome', 'about', 'contact'],
            'auth' => ['dashboard', 'profile.*'],
            'admin' => ['admin.*'],
            'api' => ['api.*'],
        ];

        $results = [];

        foreach ($segments as $segment => $patterns) {
            $routes = $this->getRoutesByPatterns($patterns);
            $auditors = $this->getAuditorsForSegment($segment);

            $results[$segment] = AuditRoutes::for($routes)
                ->setBenchmark($this->getBenchmarkForSegment($segment))
                ->run($auditors);
        }

        return $results;
    }

    private function getAuditorsForSegment(string $segment): array
    {
        return match($segment) {
            'public' => [
                // Public routes should not require authentication
                MiddlewareAuditor::make(['auth'])->setWeight(-10),
                PhpUnitAuditor::make()->setWeight(20),
            ],
            'auth' => [
                MiddlewareAuditor::make(['auth'])->setWeight(30)->setPenalty(-50),
                PhpUnitAuditor::make()->setWeight(20),
            ],
            'admin' => [
                MiddlewareAuditor::make(['auth', 'role:admin'])
                    ->setWeight(40)->setPenalty(-100),
                PolicyAuditor::make()->setWeight(30)->setPenalty(-75),
                PhpUnitAuditor::make()->setWeight(30),
            ],
            'api' => [
                MiddlewareAuditor::make(['auth:sanctum'])
                    ->setWeight(50)->setPenalty(-100),
                PhpUnitAuditor::make()->setWeight(25),
            ],
        };
    }
}
```

### Dynamic Route Discovery

Automatically discover and categorize routes based on naming conventions:

```php
class IntelligentRouteAuditor
{
    public function categorizeAndAudit(): array
    {
        $routes = $this->router->getRoutes();
        $results = [];

        // Define category-specific audits with built-in filtering
        $categories = [
            'resource_crud' => $this->auditResourceRoutes($routes),
            'api_endpoints' => $this->auditApiRoutes($routes),
            'admin_panel' => $this->auditAdminRoutes($routes),
            'public_pages' => $this->auditPublicRoutes($routes),
        ];

        return array_filter($categories);
    }

    private function auditResourceRoutes($routes): AuditedRouteCollection
    {
        $actions = ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];

        return AuditRoutes::for($routes)
            ->setBenchmark(60)
            ->run([
                PolicyAuditor::make()
                    ->setWeight(30)
                    ->when(fn($route) => collect($actions)->contains(fn($action) =>
                        str_ends_with($route->getIdentifier(), ".$action")
                    )),

                MiddlewareAuditor::make(['auth'])
                    ->setWeight(25)
                    ->when(fn($route) => collect($actions)->contains(fn($action) =>
                        str_ends_with($route->getIdentifier(), ".$action")
                    )),
            ]);
    }

    private function auditApiRoutes($routes): AuditedRouteCollection
    {
        return AuditRoutes::for($routes)
            ->setBenchmark(70)
            ->run([
                MiddlewareAuditor::make(['auth:sanctum'])
                    ->setWeight(40)
                    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'api.')),

                PhpUnitAuditor::make()
                    ->setWeight(30)
                    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'api.')),
            ]);
    }

    private function auditAdminRoutes($routes): AuditedRouteCollection
    {
        return AuditRoutes::for($routes)
            ->setBenchmark(85)
            ->run([
                PolicyAuditor::make()
                    ->setWeight(35)
                    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.')),

                MiddlewareAuditor::make(['auth', 'role:admin'])
                    ->setWeight(30)
                    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.')),

                PhpUnitAuditor::make()
                    ->setWeight(25)
                    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.')),
            ]);
    }

    private function auditPublicRoutes($routes): AuditedRouteCollection
    {
        return AuditRoutes::for($routes)
            ->setBenchmark(25)
            ->ignoreRoutes(['login', 'register', 'password.*'])
            ->run([
                PhpUnitAuditor::make()
                    ->setWeight(20)
                    ->when(fn($route) => in_array($route->getIdentifier(), [
                        'welcome', 'about', 'contact', 'terms', 'privacy'
                    ])),
            ]);
    }
}
```

## Custom Scoring Systems

### Business Logic Integration

Implement custom scoring that considers business requirements:

```php
class BusinessAwareAuditor implements AuditorInterface
{
    use Auditable;

    private array $criticalRoutes;
    private array $publicRoutes;

    public function handle(RouteInterface $route): int
    {
        $routeName = $route->getIdentifier();
        $baseScore = 0;

        // Critical business routes require enhanced security
        if (in_array($routeName, $this->criticalRoutes)) {
            $score = $this->auditCriticalRoute($route);
            return $this->getScore($score);
        }

        // Public routes have different requirements
        if (in_array($routeName, $this->publicRoutes)) {
            $score = $this->auditPublicRoute($route);
            return $this->getScore($score);
        }

        // Standard routes
        return $this->getScore($this->auditStandardRoute($route));
    }

    private function auditCriticalRoute(RouteInterface $route): int
    {
        $score = 0;

        // Critical routes must have multiple security layers
        $middlewares = $route->getMiddlewares();

        if ($this->hasAuth($middlewares)) $score += 25;
        if ($this->hasAuthorization($middlewares)) $score += 25;
        if ($this->hasRateLimit($middlewares)) $score += 15;
        if ($this->hasCSRFProtection($middlewares)) $score += 10;
        if ($this->hasHTTPSRedirect($middlewares)) $score += 5;

        // Penalty for missing any security measure
        if ($score < 80) {
            $score -= 100;  // Critical routes cannot be partially secure
        }

        return $score;
    }
}
```

### Risk-Based Scoring

Implement risk assessment based on route characteristics:

```php
class RiskAssessmentAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        $riskScore = $this->calculateRiskScore($route);
        $securityScore = $this->calculateSecurityScore($route);

        // Higher risk routes need higher security scores
        $requiredSecurity = $riskScore * 1.5;

        if ($securityScore >= $requiredSecurity) {
            return $this->getScore(100);  // Adequate security for risk level
        }

        $deficit = $requiredSecurity - $securityScore;
        return $this->getScore(-$deficit);
    }

    private function calculateRiskScore(RouteInterface $route): int
    {
        $risk = 0;
        $routeName = $route->getIdentifier();

        // Data manipulation routes are high risk
        if (str_contains($routeName, ['store', 'update', 'destroy'])) {
            $risk += 30;
        }

        // Admin routes are high risk
        if (str_starts_with($routeName, 'admin.')) {
            $risk += 40;
        }

        // Financial/payment routes are critical risk
        if (str_contains($routeName, ['payment', 'billing', 'invoice'])) {
            $risk += 50;
        }

        return min($risk, 100);
    }
}
```

## Integration Patterns

### Enterprise Monitoring Integration

Connect audit results with enterprise monitoring systems:

```php
class EnterpriseAuditIntegration
{
    public function runAndReport(): void
    {
        $result = AuditRoutes::for($this->router->getRoutes())
            ->setBenchmark(75)
            ->run($this->getEnterpriseAuditors());

        // Send metrics to monitoring system
        $this->sendMetricsToDatadog($result);

        // Create security tickets for failures
        $this->createSecurityTickets($result);

        // Update security dashboard
        $this->updateSecurityDashboard($result);
    }

    private function sendMetricsToDatadog(AuditedRouteCollection $result): void
    {
        $metrics = [
            'audit.routes.total' => $result->count(),
            'audit.routes.passed' => $result->where('status', 'passed')->count(),
            'audit.routes.failed' => $result->where('status', 'failed')->count(),
            'audit.routes.average_score' => $result->avg('score'),
        ];

        foreach ($metrics as $metric => $value) {
            Datadog::gauge($metric, $value, ['environment' => app()->environment()]);
        }
    }

    private function createSecurityTickets(AuditedRouteCollection $result): void
    {
        $criticalFailures = $result->where('score', '<', -50);

        foreach ($criticalFailures as $route) {
            Jira::createSecurityTicket([
                'summary' => "Critical security issue: {$route->getIdentifier()}",
                'description' => $this->formatSecurityIssue($route),
                'priority' => 'Critical',
                'labels' => ['security', 'audit', 'critical'],
            ]);
        }
    }
}
```

### Multi-Tenant Security Auditing

Handle security auditing in multi-tenant applications:

```php
class TenantAwareAuditor
{
    public function auditAllTenants(): array
    {
        $results = [];

        foreach (Tenant::all() as $tenant) {
            $results[$tenant->id] = $this->auditTenant($tenant);
        }

        return $results;
    }

    private function auditTenant(Tenant $tenant): AuditedRouteCollection
    {
        // Switch to tenant context
        tenancy()->initialize($tenant);

        // Get tenant-specific routes
        $routes = $this->getTenantRoutes($tenant);

        // Apply tenant-specific security requirements
        $auditors = $this->getTenantAuditors($tenant);

        $result = AuditRoutes::for($routes)
            ->setBenchmark($tenant->security_benchmark ?? 50)
            ->run($auditors);

        // Log tenant-specific issues
        $this->logTenantSecurityIssues($tenant, $result);

        return $result;
    }

    private function getTenantAuditors(Tenant $tenant): array
    {
        $baseAuditors = [
            PolicyAuditor::make()->setWeight(25),
            PhpUnitAuditor::make()->setWeight(20),
        ];

        // Enterprise tenants get enhanced security requirements
        if ($tenant->tier === 'enterprise') {
            $baseAuditors[] = MiddlewareAuditor::make(['auth', '2fa'])
                ->setWeight(30);
        }

        return $baseAuditors;
    }
}
```

## Performance Optimization

### Caching Strategy

Implement intelligent caching for large applications:

```php
class CachedAuditService
{
    private Cache $cache;
    private int $cacheTime = 3600; // 1 hour

    public function getCachedAudit(string $cacheKey): ?AuditedRouteCollection
    {
        return $this->cache->get("route_audit:{$cacheKey}");
    }

    public function runWithCache(array $routes, array $auditors): AuditedRouteCollection
    {
        $cacheKey = $this->generateCacheKey($routes, $auditors);

        return $this->cache->remember(
            "route_audit:{$cacheKey}",
            $this->cacheTime,
            fn() => AuditRoutes::for($routes)->run($auditors)
        );
    }

    private function generateCacheKey(array $routes, array $auditors): string
    {
        $routeHash = md5(serialize(array_map(fn($r) => $r->getIdentifier(), $routes)));
        $auditorHash = md5(serialize($auditors));

        return "{$routeHash}:{$auditorHash}";
    }

    public function invalidateCache(string $pattern = '*'): void
    {
        $keys = $this->cache->keys("route_audit:{$pattern}");
        $this->cache->deleteMultiple($keys);
    }
}
```

### Parallel Processing

Process large route sets in parallel:

```php
class ParallelAuditProcessor
{
    public function processInParallel(array $routes, array $auditors): AuditedRouteCollection
    {
        $chunks = array_chunk($routes, 50); // Process in chunks of 50
        $results = [];

        $promises = [];
        foreach ($chunks as $chunk) {
            $promises[] = $this->processChunkAsync($chunk, $auditors);
        }

        // Wait for all chunks to complete
        $chunkResults = Promise::all($promises)->wait();

        // Merge results
        return $this->mergeResults($chunkResults);
    }

    private function processChunkAsync(array $routes, array $auditors): Promise
    {
        return new Promise(function() use ($routes, $auditors) {
            return AuditRoutes::for($routes)->run($auditors);
        });
    }
}
```

## Custom Export Formats

### Enterprise Reporting

Create custom export formats for enterprise needs:

```php
class EnterpriseReportExporter
{
    public function exportExecutiveSummary(AuditedRouteCollection $results): string
    {
        $summary = [
            'security_posture' => $this->calculateSecurityPosture($results),
            'risk_assessment' => $this->assessRisk($results),
            'compliance_status' => $this->checkCompliance($results),
            'recommendations' => $this->generateRecommendations($results),
        ];

        return json_encode($summary, JSON_PRETTY_PRINT);
    }

    public function exportComplianceReport(AuditedRouteCollection $results): string
    {
        return $this->generateComplianceXML($results);
    }

    public function exportSecurityMetrics(AuditedRouteCollection $results): array
    {
        return [
            'total_routes' => $results->count(),
            'secure_routes' => $results->where('score', '>', 0)->count(),
            'at_risk_routes' => $results->where('score', '<', 0)->count(),
            'coverage_percentage' => $this->calculateCoverage($results),
            'security_score_distribution' => $this->getScoreDistribution($results),
        ];
    }
}
```

## Next Steps

- **[Custom Auditors Guide](custom-auditors.md)**: Build specialized security auditors
- **[Testing Guide](testing.md)**: Advanced testing patterns and custom assertions
- **[CI Integration Guide](ci-integration.md)**: Automate enterprise security workflows
- **[Configuration Guide](../getting-started/configuration.md)**: Fine-tune advanced settings
- **[Architecture Overview](../reference/architecture/overview.md)**: Understand system internals
- **[Troubleshooting](troubleshooting.md)**: Resolve complex configuration issues