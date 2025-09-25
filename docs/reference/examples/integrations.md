# Framework and Tool Integrations

Examples of integrating Audit Routes with popular PHP frameworks, security tools, and development platforms. These integrations extend route security auditing beyond Laravel applications.

## Framework Support

### Lumen Framework

Lumen requires additional setup for full Audit Routes functionality:

**Installation**:
```bash
composer require mydevnl/audit-routes --dev
```

**Service Provider Registration**:
```php
// bootstrap/app.php
$app->register(MyDev\AuditRoutes\AuditRoutesServiceProvider::class);

// Enable facades if needed
$app->withFacades();

// Register configuration
$app->configure('audit-routes');
```

**Configuration Setup**:
```php
// config/audit-routes.php (create manually)
<?php
return [
    'ignored-routes' => ['api/status', 'api/health'],
    'benchmark' => 50,
    'tests' => [
        'directory' => 'tests',
        'implementation' => \Laravel\Lumen\Testing\TestCase::class,
    ],
];
```

**Custom Command Registration**:
```php
// app/Console/Kernel.php
class Kernel extends ConsoleKernel
{
    protected $commands = [
        MyDev\AuditRoutes\Console\Commands\AuditCommand::class,
        MyDev\AuditRoutes\Console\Commands\AuditReportCommand::class,
    ];
}
```

### Symfony Integration

Audit Routes core engine can be extended to work with Symfony applications through route adapters and custom auditors:

**Symfony Route Adapter**:
```php
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use Symfony\Component\Routing\Route as SymfonyRouteCore;

class SymfonyRoute implements RouteInterface
{
    private SymfonyRouteCore $route;
    private string $name;

    public function __construct(SymfonyRouteCore $route, string $name)
    {
        $this->route = $route;
        $this->name = $name;
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }

    public function getUri(): string
    {
        return $this->route->getPath();
    }

    public function getMethod(): string
    {
        $methods = $this->route->getMethods();
        return empty($methods) ? 'GET' : $methods[0];
    }

    public function getMiddlewares(): array
    {
        $middlewares = [];

        // Map Symfony security firewall rules to middleware
        if ($firewall = $this->getFirewallName()) {
            $middlewares[] = new Middleware("firewall:{$firewall}");
        }

        // Map security annotations to middleware
        if ($security = $this->route->getRequirement('_security')) {
            $middlewares[] = new Middleware("security:{$security}");
        }

        // Map HTTPS requirements
        $schemes = $this->route->getSchemes();
        if (in_array('https', $schemes) && !in_array('http', $schemes)) {
            $middlewares[] = new Middleware('https_required');
        }

        return $middlewares;
    }

    public function hasScopedBindings(): ?bool
    {
        // Analyze route parameters for scoping requirements
        $requirements = $this->route->getRequirements();
        $compiledRoute = $this->route->compile();

        if (count($compiledRoute->getPathVariables()) > 1) {
            // Check if nested parameters have scoping constraints
            return $this->hasParameterScopingConstraints($requirements);
        }

        return null; // Not applicable for single parameter routes
    }

    private function getFirewallName(): ?string
    {
        // Extract firewall name from route defaults or context
        return $this->route->getDefault('_firewall');
    }
}
```

**Integration Setup**:
```php
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;
use Symfony\Component\Routing\RouteCollection;

class SymfonyAuditIntegration
{
    public function auditSymfonyRoutes(RouteCollection $routeCollection): array
    {
        // Adapt Symfony routes to Audit Routes format
        $auditRoutes = [];
        foreach ($routeCollection->all() as $name => $route) {
            $auditRoutes[] = new SymfonyRoute($route, $name);
        }

        // Use existing Audit Routes engine with adapted routes
        $result = AuditRoutes::for($auditRoutes)
            ->setBenchmark(50)
            ->run([
                PhpUnitAuditor::make()->setWeight(20),
                SymfonySecurityAuditor::make()->setWeight(30),
                SymfonyAccessControlAuditor::make()->setWeight(25),
            ]);

        return $result;
    }
}
```

**Custom Symfony Auditors**:
```php
class SymfonySecurityAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        $score = 0;
        $middlewares = $route->getMiddlewares();

        // Check for firewall protection
        $hasFirewall = collect($middlewares)->contains(
            fn($m) => str_starts_with($m->getName(), 'firewall:')
        );

        if ($hasFirewall) {
            $score += 1;
        }

        // Check for security annotations
        $hasSecurity = collect($middlewares)->contains(
            fn($m) => str_starts_with($m->getName(), 'security:')
        );

        if ($hasSecurity) {
            $score += 1;
        }

        return $this->getScore($score);
    }
}

class SymfonyAccessControlAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        // Analyze Symfony access control (equivalent to Laravel policies)
        $middlewares = $route->getMiddlewares();

        $hasAccessControl = collect($middlewares)->contains(function($middleware) {
            $name = $middleware->getName();
            return str_contains($name, 'is_granted') ||
                   str_contains($name, 'access_control') ||
                   str_contains($name, 'role:');
        });

        return $this->getScore($hasAccessControl ? 1 : 0);
    }
}
```

## Security Tool Integrations

### SonarQube Integration

Export audit results for SonarQube security analysis:

**Custom SonarQube Exporter**:
```php
class SonarQubeExporter
{
    public function exportSecurityIssues(AuditedRouteCollection $results): void
    {
        $issues = [];

        foreach ($results as $route) {
            if ($route->getScore() < 0) {
                $issues[] = [
                    'engineId' => 'audit-routes',
                    'ruleId' => 'insecure-route',
                    'severity' => $this->mapSeverity($route->getScore()),
                    'type' => 'SECURITY_HOTSPOT',
                    'primaryLocation' => [
                        'message' => "Route {$route->getIdentifier()} has security vulnerabilities",
                        'filePath' => $this->findRouteFile($route),
                        'textRange' => $this->findRouteLocation($route),
                    ],
                ];
            }
        }

        $this->writeSonarQubeReport($issues);
    }

    private function mapSeverity(int $score): string
    {
        return match(true) {
            $score < -50 => 'CRITICAL',
            $score < -25 => 'MAJOR',
            $score < 0 => 'MINOR',
            default => 'INFO'
        };
    }

    private function writeSonarQubeReport(array $issues): void
    {
        $report = [
            'issues' => $issues
        ];

        file_put_contents(
            'storage/exports/sonarqube-security.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
}
```

**SonarQube Properties**:
```properties
# sonar-project.properties
sonar.externalIssuesReportPaths=storage/exports/sonarqube-security.json
sonar.security.hotspots.reportPaths=storage/exports/sonarqube-security.json
```

### OWASP ZAP Integration

Generate baseline configurations for OWASP ZAP based on route analysis:

**ZAP Baseline Generator**:
```php
class OWASPZAPIntegration
{
    public function generateZAPBaseline(AuditedRouteCollection $routes): void
    {
        $config = [
            'env' => [
                'contexts' => $this->generateContexts($routes),
                'includePaths' => $this->getIncludePaths($routes),
                'excludePaths' => $this->getExcludePaths($routes),
            ],
            'jobs' => [
                [
                    'type' => 'passiveScan-wait',
                    'parameters' => ['maxAlertsPerRule' => 10]
                ],
                [
                    'type' => 'activeScan',
                    'parameters' => [
                        'context' => 'audit-routes-context',
                        'policy' => 'API-minimal'
                    ]
                ]
            ]
        ];

        file_put_contents(
            'storage/exports/zap-baseline.yaml',
            yaml_emit($config)
        );
    }

    private function generateContexts(AuditedRouteCollection $routes): array
    {
        $contexts = [];

        // Group routes by security level
        $securityLevels = [
            'high-security' => $routes->where('score', '>', 75),
            'medium-security' => $routes->where('score', '>=', 25)->where('score', '<=', 75),
            'low-security' => $routes->where('score', '<', 25),
        ];

        foreach ($securityLevels as $level => $levelRoutes) {
            if ($levelRoutes->isNotEmpty()) {
                $contexts[] = [
                    'name' => $level,
                    'urls' => $levelRoutes->map(fn($r) => $this->routeToUrl($r))->toArray(),
                    'authentication' => $this->getAuthConfig($levelRoutes),
                ];
            }
        }

        return $contexts;
    }
}
```

### Burp Suite Integration

Export route information for Burp Suite security scanning:

**Burp Suite Configuration Export**:
```php
class BurpSuiteExporter
{
    public function exportSiteMap(AuditedRouteCollection $routes): void
    {
        $siteMap = [
            'target' => [
                'scope' => [
                    'include' => $this->generateIncludeRules($routes),
                    'exclude' => $this->generateExcludeRules($routes),
                ]
            ],
            'spider' => [
                'maxDepth' => 5,
                'checkRobotsTxt' => false,
            ],
            'scanner' => [
                'auditOptimization' => 'normal',
                'issues' => $this->getSecurityChecks($routes),
            ]
        ];

        file_put_contents(
            'storage/exports/burp-config.json',
            json_encode($siteMap, JSON_PRETTY_PRINT)
        );
    }

    private function generateIncludeRules(AuditedRouteCollection $routes): array
    {
        return $routes->map(function($route) {
            return [
                'enabled' => true,
                'file' => '^' . preg_quote($this->routeToPath($route)) . '$',
                'host' => '^.*$',
                'port' => '^(80|443)$',
                'protocol' => 'any',
            ];
        })->toArray();
    }
}
```

## IDE Integrations

### PHPStorm Integration

Create PHPStorm inspection profile for route security:

**Inspection Profile**:
```xml
<!-- .idea/inspectionProfiles/RouteSecurityProfile.xml -->
<component name="InspectionProjectProfileManager">
  <profile version="1.0">
    <option name="myName" value="Route Security" />
    <inspection_tool class="PhpUnhandledExceptionInspection" enabled="true" level="WARNING" enabled_by_default="true" />

    <!-- Custom route security inspections -->
    <inspection_tool class="RouteSecurityInspection" enabled="true" level="ERROR" enabled_by_default="true">
      <option name="checkAuthentication" value="true" />
      <option name="checkAuthorization" value="true" />
      <option name="checkTestCoverage" value="true" />
    </inspection_tool>
  </profile>
</component>
```

**PHPStorm Plugin Configuration**:
```php
// .phpstorm.meta.php
<?php
namespace PHPSTORM_META {
    registerArgumentsSet('route_security_levels',
        'public',
        'authenticated',
        'authorized',
        'admin'
    );

    expectedArguments(\MyDev\AuditRoutes\AuditRoutes::setBenchmark(), 0, 0, 25, 50, 75, 100);
    expectedArguments(\MyDev\AuditRoutes\Auditors\MiddlewareAuditor::make(), 0,
        argumentsSet('laravel_middleware')
    );
}
```

### VS Code Integration

**VS Code Task Configuration**:
```json
// .vscode/tasks.json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Route Security Audit",
            "type": "shell",
            "command": "php",
            "args": ["artisan", "route:audit", "-vv"],
            "group": {
                "kind": "test",
                "isDefault": false
            },
            "presentation": {
                "echo": true,
                "reveal": "always",
                "focus": false,
                "panel": "shared"
            },
            "problemMatcher": {
                "pattern": {
                    "regexp": "^(.*)\\s+(\\-?\\d+)\\s*$",
                    "file": 1,
                    "severity": 2
                }
            }
        },
        {
            "label": "Generate Security Report",
            "type": "shell",
            "command": "php",
            "args": ["artisan", "route:audit-report"],
            "group": "build"
        }
    ]
}
```

**VS Code Settings**:
```json
// .vscode/settings.json
{
    "php.suggest.basic": false,
    "audit-routes.benchmark": 50,
    "audit-routes.autoRun": false,
    "files.associations": {
        "*.audit": "json"
    }
}
```

## Monitoring Platform Integrations

### Datadog Integration

Send route security metrics to Datadog:

**Datadog Metrics Exporter**:
```php
use DataDog\DogStatsDClient;

class DatadogSecurityMetrics
{
    private DogStatsDClient $statsd;

    public function __construct(DogStatsDClient $statsd)
    {
        $this->statsd = $statsd;
    }

    public function sendRouteSecurityMetrics(AuditedRouteCollection $results): void
    {
        $tags = [
            'environment:' . app()->environment(),
            'application:' . config('app.name'),
        ];

        // Overall metrics
        $this->statsd->gauge('route_security.total_routes', $results->count(), $tags);
        $this->statsd->gauge('route_security.passed_routes',
            $results->where('status', 'passed')->count(), $tags);
        $this->statsd->gauge('route_security.failed_routes',
            $results->where('status', 'failed')->count(), $tags);

        // Average score
        $avgScore = $results->avg('score');
        $this->statsd->gauge('route_security.average_score', $avgScore, $tags);

        // Security level distribution
        $securityLevels = [
            'critical' => $results->where('score', '<', -25)->count(),
            'low' => $results->where('score', '>=', -25)->where('score', '<', 25)->count(),
            'medium' => $results->where('score', '>=', 25)->where('score', '<', 75)->count(),
            'high' => $results->where('score', '>=', 75)->count(),
        ];

        foreach ($securityLevels as $level => $count) {
            $this->statsd->gauge("route_security.level.$level", $count, $tags);
        }

        // Custom business metrics
        $this->sendBusinessSpecificMetrics($results, $tags);
    }

    private function sendBusinessSpecificMetrics(AuditedRouteCollection $results, array $tags): void
    {
        // Use built-in filtering to get metrics for specific route categories
        $apiRoutes = $results->getRoutes()->filter(fn($r) => str_starts_with($r->getIdentifier(), 'api.'));
        $this->statsd->gauge('route_security.api.total', $apiRoutes->count(), $tags);
        $this->statsd->gauge('route_security.api.secure',
            $apiRoutes->filter(fn($r) => $r->getScore() > 50)->count(), $tags);

        // Admin route security metrics
        $adminRoutes = $results->getRoutes()->filter(fn($r) => str_starts_with($r->getIdentifier(), 'admin.'));
        $this->statsd->gauge('route_security.admin.total', $adminRoutes->count(), $tags);
        $this->statsd->gauge('route_security.admin.secure',
            $adminRoutes->filter(fn($r) => $r->getScore() > 75)->count(), $tags);
    }
}
```

### New Relic Integration

```php
class NewRelicSecurityInsights
{
    public function recordSecurityEvent(AuditedRouteCollection $results): void
    {
        if (!extension_loaded('newrelic')) {
            return;
        }

        $securitySummary = [
            'total_routes' => $results->count(),
            'secure_routes' => $results->where('score', '>', 0)->count(),
            'vulnerable_routes' => $results->where('score', '<', 0)->count(),
            'average_security_score' => $results->avg('score'),
            'audit_timestamp' => time(),
        ];

        newrelic_record_custom_event('RouteSecurityAudit', $securitySummary);

        // Record individual critical vulnerabilities
        $criticalVulns = $results->where('score', '<', -50);
        foreach ($criticalVulns as $route) {
            newrelic_record_custom_event('CriticalRouteVulnerability', [
                'route_name' => $route->getIdentifier(),
                'security_score' => $route->getScore(),
                'route_method' => $route->getMethod(),
                'route_uri' => $route->getUri(),
            ]);
        }
    }
}
```

### Grafana Dashboard Integration

Generate Grafana dashboard JSON for route security visualization:

**Grafana Dashboard Generator**:
```php
class GrafanaDashboardGenerator
{
    public function generateSecurityDashboard(): array
    {
        return [
            'dashboard' => [
                'title' => 'Route Security Metrics',
                'tags' => ['security', 'routes', 'laravel'],
                'panels' => [
                    $this->createOverviewPanel(),
                    $this->createTrendPanel(),
                    $this->createVulnerabilityPanel(),
                    $this->createCompliancePanel(),
                ],
            ]
        ];
    }

    private function createOverviewPanel(): array
    {
        return [
            'title' => 'Security Overview',
            'type' => 'stat',
            'targets' => [
                [
                    'expr' => 'route_security_total_routes',
                    'legendFormat' => 'Total Routes'
                ],
                [
                    'expr' => 'route_security_passed_routes',
                    'legendFormat' => 'Secure Routes'
                ],
                [
                    'expr' => 'route_security_failed_routes',
                    'legendFormat' => 'Vulnerable Routes'
                ]
            ],
        ];
    }
}
```

## Testing Framework Integrations

### Pest PHP Integration

Custom Pest helpers for route security testing:

```php
// tests/Pest.php
<?php

use MyDev\AuditRoutes\Testing\Concerns\AssertsAuditRoutes;

uses(AssertsAuditRoutes::class)->in('Feature');

// Custom Pest helpers
function auditRoute(string $routeName, int $expectedScore = null)
{
    return test()->auditSpecificRoute($routeName, $expectedScore);
}

function auditRoutes(array $routes, array $auditors = [], int $benchmark = 0)
{
    return test()->assertAuditRoutesOk($routes, $auditors, 'Routes failed security audit', [], $benchmark);
}
```

**Pest Test Examples**:
```php
// tests/Feature/RouteSecurityTest.php
<?php

use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;

describe('Route Security', function () {
    it('ensures all admin routes are properly secured')
        ->auditRoutes(['admin.*'], [
            PolicyAuditor::make(),
            PhpUnitAuditor::make(),
        ], 50);

    it('validates API authentication')
        ->auditRoutes(['api.*'], [
            MiddlewareAuditor::make(['auth:sanctum']),
        ]);
});
```

### Codeception Integration

```php
// tests/_support/Helper/SecurityAudit.php
<?php
namespace Helper;

use MyDev\AuditRoutes\AuditRoutes;
use Codeception\Module;

class SecurityAudit extends Module
{
    public function seeRouteIsSecure(string $routeName, int $minimumScore = 50): void
    {
        $routes = app('router')->getRoutes()->getByName($routeName);
        $result = AuditRoutes::for([$routes])->run([
            PolicyAuditor::make(),
            PhpUnitAuditor::make(),
        ]);

        $routeResult = $result->first();
        $this->assertGreaterThanOrEqual(
            $minimumScore,
            $routeResult->getScore(),
            "Route {$routeName} scored {$routeResult->getScore()}, expected at least {$minimumScore}"
        );
    }

    public function dontSeeSecurityVulnerabilities(array $routes): void
    {
        $result = AuditRoutes::for($routes)->setBenchmark(0)->run([
            PolicyAuditor::make(),
            MiddlewareAuditor::make(['auth']),
        ]);

        $vulnerableRoutes = $result->where('score', '<', 0);
        $this->assertEmpty(
            $vulnerableRoutes,
            'Found security vulnerabilities in routes: ' . $vulnerableRoutes->pluck('identifier')->join(', ')
        );
    }
}
```

## Deployment Platform Integrations

### Docker Integration

**Multi-stage Dockerfile with Security Audit**:
```dockerfile
# Security audit stage
FROM php:8.2-cli as security-audit

RUN apt-get update && apt-get install -y git unzip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --dev --no-scripts --no-interaction

COPY . .
RUN composer install --dev --optimize-autoloader --no-interaction

# Run security audit
RUN php artisan route:audit --benchmark 50 --export json --filename security-audit.json

# Fail build if security audit fails
RUN if [ -f "storage/exports/audit-routes/security-audit.json" ]; then \
        FAILED=$(jq '.summary.failed // 0' storage/exports/audit-routes/security-audit.json); \
        if [ "$FAILED" -gt 0 ]; then \
            echo "Security audit failed: $FAILED routes below threshold"; \
            exit 1; \
        fi; \
    fi

# Production stage
FROM php:8.2-fpm as production
# ... production configuration
COPY --from=security-audit /app /var/www/html
```

### Kubernetes Integration

**Security Policy Enforcement**:
```yaml
# k8s/security-audit-job.yaml
apiVersion: batch/v1
kind: Job
metadata:
  name: route-security-audit
spec:
  template:
    spec:
      containers:
      - name: audit-routes
        image: myapp:latest
        command: ["php", "artisan", "route:audit"]
        args: ["--benchmark", "75", "--export", "json"]
        env:
        - name: APP_ENV
          value: "production"
        volumeMounts:
        - name: audit-results
          mountPath: /app/storage/exports
      volumes:
      - name: audit-results
        persistentVolumeClaim:
          claimName: audit-storage
      restartPolicy: Never
  backoffLimit: 3
```

## Custom Integration Patterns

### Webhook Integration

```php
class SecurityAuditWebhook
{
    public function sendAuditResults(AuditedRouteCollection $results, string $webhookUrl): void
    {
        $payload = [
            'timestamp' => now()->toISOString(),
            'application' => config('app.name'),
            'environment' => app()->environment(),
            'summary' => [
                'total_routes' => $results->count(),
                'passed_routes' => $results->where('status', 'passed')->count(),
                'failed_routes' => $results->where('status', 'failed')->count(),
                'average_score' => $results->avg('score'),
            ],
            'critical_issues' => $results->where('score', '<', -25)->map(function($route) {
                return [
                    'route' => $route->getIdentifier(),
                    'score' => $route->getScore(),
                    'issues' => $this->identifyIssues($route),
                ];
            })->toArray(),
        ];

        Http::post($webhookUrl, $payload);
    }
}
```

## Next Steps

- **[Real-world Examples](real-world.md)**: Production use cases and implementation patterns
- **[CI Integration Guide](../../guides/ci-integration.md)**: Automation workflows
- **[Advanced Usage Guide](../../guides/advanced-usage.md)**: Complex configurations
- **[Custom Auditors Guide](../../guides/custom-auditors.md)**: Building specialized integrations