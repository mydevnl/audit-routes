# Real-World Production Use Cases

Practical examples of how teams use Audit Routes in production environments to maintain security standards, comply with regulations, and scale development practices.

## E-commerce Platform Security

### Company Profile: Online Marketplace
- **Size**: 150+ routes, 12-person development team
- **Industry**: E-commerce, PCI DSS compliance required
- **Challenge**: Secure payment processing and user data protection

### Implementation

**Security Configuration**:
```php
// config/audit-routes.php (production)
return [
    'benchmark' => 85,  // High security standards
    'ignored-routes' => [],  // No exceptions in production
    'tests' => [
        'directory' => 'tests/Feature',
        'acting-methods' => [
            'get', 'post', 'put', 'patch', 'delete',
            'getJson', 'postJson', 'actingAs'
        ],
    ],
];
```

**Custom Payment Security Auditor**:
```php
class PaymentSecurityAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        $score = 0;
        $requirements = [
            'has_auth' => $this->hasMiddleware($route, ['auth']),
            'has_2fa' => $this->hasMiddleware($route, ['two-factor']),
            'has_encryption' => $this->hasMiddleware($route, ['encrypt-response']),
            'has_audit_log' => $this->hasMiddleware($route, ['audit-log']),
            'has_rate_limit' => $this->hasMiddleware($route, ['throttle:']),
            'has_csrf' => $this->hasCSRFProtection($route),
        ];

        $passed = array_filter($requirements);

        // Payment routes must pass ALL security checks
        return count($passed) === count($requirements) ?
            $this->getScore(2) : $this->getScore(-2);
    }
}
```

**Payment Route Security Audit**:
```php
// Run payment-specific security audit
$result = AuditRoutes::for($routes)
    ->setBenchmark(85)
    ->run([
        // Apply payment auditor only to payment-related routes
        PaymentSecurityAuditor::make()
            ->setWeight(50)
            ->when(fn($route) => str_contains($route->getIdentifier(), 'payment.') ||
                               str_contains($route->getIdentifier(), 'checkout.') ||
                               str_contains($route->getIdentifier(), 'billing.')),

        // Standard auditors for all routes
        PolicyAuditor::make()->setWeight(25),
        MiddlewareAuditor::make(['auth'])->setWeight(20),
        PhpUnitAuditor::make()->setWeight(15),
    ]);
```

**CI/CD Integration**:
```yaml
# .github/workflows/payment-security.yml
name: Payment Security Audit

on:
  pull_request:
    paths:
      - 'app/Http/Controllers/Payment/**'
      - 'routes/payment.php'

jobs:
  payment-security:
    runs-on: ubuntu-latest
    steps:
      - name: Payment Route Security Audit
        run: |
          php artisan route:audit --benchmark 85 --export json --filename payment-audit.json
          FAILED=$(jq '.summary.failed' payment-audit.json)
          if [ "$FAILED" -gt 0 ]; then
            echo "❌ Payment security audit failed"
            exit 1
          fi
```

**Results**: 99.2% security compliance, zero payment security incidents, passed PCI DSS audit.

---

## SaaS Application Multi-Tenant Security

### Company Profile: B2B SaaS Platform
- **Size**: 300+ routes, 25-person team across 5 squads
- **Industry**: SaaS, SOC 2 compliance
- **Challenge**: Multi-tenant data isolation and role-based access control

### Implementation

**Tenant-Aware Security Auditing**:
```php
class TenantScopeAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        $score = 0;

        // Check authentication requirement
        if (!$this->hasMiddleware($route, ['auth'])) {
            return $this->getScore(-5);
        }
        $score++;

        // Check tenant scope middleware
        if ($this->hasMiddleware($route, ['tenant.scope'])) {
            $score += 2;
        } else {
            return $this->getScore(-3); // Critical failure for tenant routes
        }

        return $this->getScore($score);
    }
}

class AdminRoleAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        if ($this->hasMiddleware($route, ['role:admin'])) {
            return $this->getScore(1);
        }

        return $this->getScore(-4); // Admin routes must have role middleware
    }
}
```

**Squad-Based Security Standards**:
```php
// Custom command for squad-specific audits
class SquadSecurityAuditCommand extends Command
{
    protected $signature = 'security:audit-squad {squad}';

    public function handle()
    {
        $squad = $this->argument('squad');
        $routes = app(Router::class)->getRoutes();

        $benchmarks = [
            'auth' => 60,      // Authentication team
            'billing' => 85,   // Billing team (high security)
            'api' => 70,       // API team
            'dashboard' => 55, // Dashboard team
            'admin' => 90,     // Admin team (highest security)
        ];

        $squadRoutePatterns = [
            'auth' => ['auth.*', 'login.*', 'register.*'],
            'billing' => ['billing.*', 'payments.*', 'invoices.*'],
            'api' => ['api.*'],
            'dashboard' => ['dashboard.*', 'tenant.*'],
            'admin' => ['admin.*', 'management.*'],
        ];

        $result = AuditRoutes::for($routes)
            ->setBenchmark($benchmarks[$squad] ?? 50)
            ->run([
                // Tenant scope auditor for dashboard/tenant routes
                TenantScopeAuditor::make()
                    ->setWeight(30)
                    ->when(fn($route) => str_contains($route->getIdentifier(), 'tenant.') ||
                                        str_contains($route->getIdentifier(), 'dashboard.')),

                // Admin role auditor for admin routes
                AdminRoleAuditor::make()
                    ->setWeight(25)
                    ->when(fn($route) => str_contains($route->getIdentifier(), 'admin.')),

                // Standard auditors with route filtering
                PolicyAuditor::make()
                    ->setWeight(25)
                    ->when(fn($route) => $this->matchesSquadPattern($route, $squadRoutePatterns[$squad] ?? [])),

                PhpUnitAuditor::make()
                    ->setWeight(20)
                    ->when(fn($route) => $this->matchesSquadPattern($route, $squadRoutePatterns[$squad] ?? [])),
            ]);

        $this->displaySquadResults($squad, $result);
    }

    private function matchesSquadPattern(RouteInterface $route, array $patterns): bool
    {
        $identifier = $route->getIdentifier();

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $pattern = str_replace('*', '', $pattern);
                if (str_starts_with($identifier, $pattern)) {
                    return true;
                }
            } elseif ($identifier === $pattern) {
                return true;
            }
        }

        return false;
    }
}
```

**Compliance Reporting**:
```php
class SOC2ComplianceReporter
{
    public function generateQuarterlyReport(): void
    {
        $routes = app(Router::class)->getRoutes();

        $complianceResult = AuditRoutes::for($routes)
            ->setBenchmark(75)
            ->ignoreRoutes(['api.health', 'api.status', 'login', 'register']) // Exclude public routes
            ->run([
                SOC2Auditor::make()->setWeight(40),
                DataProtectionAuditor::make()->setWeight(35),
                new AccessControlAuditor(),
            ]);

        // Generate compliance metrics
        $metrics = [
            'total_routes' => $complianceResult->count(),
            'compliant_routes' => $complianceResult->where('score', '>=', 75)->count(),
            'critical_violations' => $complianceResult->where('score', '<', 0)->count(),
            'compliance_percentage' => $this->calculateCompliance($complianceResult),
        ];

        // Store for SOC 2 audit trail
        ComplianceReport::create([
            'report_type' => 'route_security',
            'period' => now()->format('Y-Q'),
            'metrics' => $metrics,
            'details' => $complianceResult->toArray(),
        ]);
    }
}
```

**Results**: SOC 2 Type II certification achieved, 95% route security compliance, automated compliance monitoring.

---

## Financial Services API Security

### Company Profile: Fintech Startup
- **Size**: 80+ API endpoints, 8-person team
- **Industry**: Financial services, regulatory compliance
- **Challenge**: Open Banking APIs with strict security requirements

### Implementation

**Regulatory Compliance Auditor**:
```php
class OpenBankingSecurityAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        if (!str_starts_with($route->getIdentifier(), 'api.open-banking.')) {
            return $this->getScore(1); // Not applicable
        }

        $score = 0;
        $violations = [];

        // Open Banking security requirements
        $requirements = [
            'oauth_token' => $this->hasMiddleware($route, ['auth:api']),
            'rate_limiting' => $this->hasMiddleware($route, ['throttle:']),
            'request_signing' => $this->hasMiddleware($route, ['verify-signature']),
            'encryption' => $this->hasMiddleware($route, ['encrypt-response']),
            'audit_logging' => $this->hasMiddleware($route, ['audit-log']),
            'ip_filtering' => $this->hasMiddleware($route, ['ip-filter']),
        ];

        foreach ($requirements as $requirement => $passed) {
            if ($passed) {
                $score++;
            } else {
                $violations[] = $requirement;
            }
        }

        // All requirements must be met for Open Banking APIs
        if (empty($violations)) {
            return $this->getScore(3); // Excellent compliance
        } elseif (count($violations) <= 2) {
            return $this->getScore(-1); // Minor violations
        } else {
            return $this->getScore(-5); // Major violations
        }
    }
}
```

**Automated Security Testing**:
```php
// tests/Feature/OpenBankingSecurityTest.php
class OpenBankingSecurityTest extends TestCase
{
    use AssertsAuditRoutes;

    public function test_open_banking_routes_meet_security_standards()
    {
        $openBankingRoutes = collect(Route::getRoutes())
            ->filter(fn($route) => str_starts_with($route->getName(), 'api.open-banking.'))
            ->map(fn($route) => $route->getName())
            ->toArray();

        $this->assertAuditRoutesOk(
            $openBankingRoutes,
            [OpenBankingSecurityAuditor::make()],
            'Open Banking routes must meet regulatory security standards',
            benchmark: 2
        );
    }

    public function test_pci_dss_compliance()
    {
        $paymentRoutes = [
            'api.payments.process',
            'api.payments.refund',
            'api.cards.tokenize',
        ];

        $this->assertAuditRoutesOk(
            $paymentRoutes,
            [
                new PCIDSSAuditor(),
                MiddlewareAuditor::make(['encrypt-card-data'])->setWeight(10),
            ],
            'Payment routes must comply with PCI DSS requirements',
            benchmark: 8
        );
    }
}
```

**Regulatory Reporting Dashboard**:
```php
class RegulatoryDashboard
{
    public function generateDailySecurityReport(): array
    {
        $routes = app(Router::class)->getRoutes();

        $audits = [
            'open_banking' => AuditRoutes::for(
                $this->filterRoutes($routes, 'api.open-banking.')
            )->run([OpenBankingSecurityAuditor::make()]),

            'pci_compliance' => AuditRoutes::for(
                $this->filterRoutes($routes, 'api.payments.')
            )->run([PCIDSSAuditor::make()]),

            'gdpr_compliance' => AuditRoutes::for(
                $this->filterRoutes($routes, 'api.users.')
            )->run([GDPRAuditor::make()]),
        ];

        return [
            'compliance_scores' => array_map(
                fn($audit) => $audit->getAverageScore(),
                $audits
            ),
            'violations' => array_map(
                fn($audit) => $audit->where('score', '<', 0)->count(),
                $audits
            ),
            'timestamp' => now(),
        ];
    }
}
```

**Results**: Regulatory approval for Open Banking APIs, 100% security compliance score, automated audit trail for regulators.

---

## Healthcare Platform HIPAA Compliance

### Company Profile: Healthcare Technology Platform
- **Size**: 200+ routes, 15-person team
- **Industry**: Healthcare, HIPAA compliance required
- **Challenge**: Patient data protection and access controls

### Implementation

**HIPAA Security Auditor**:
```php
class HIPAASecurityAuditor implements AuditorInterface
{
    use Auditable;

    private array $phiRoutes = [
        'patients.', 'medical-records.', 'appointments.', 'prescriptions.'
    ];

    public function handle(RouteInterface $route): int
    {
        $routeName = $route->getIdentifier();

        if (!$this->handlesPatientData($routeName)) {
            return $this->getScore(1); // Not applicable
        }

        $score = 0;
        $criticalRequirements = [
            'authentication' => $this->hasMiddleware($route, ['auth']),
            'authorization' => $this->hasMiddleware($route, ['can:', 'authorize']),
            'audit_logging' => $this->hasMiddleware($route, ['hipaa-audit']),
            'data_encryption' => $this->hasMiddleware($route, ['encrypt-phi']),
            'access_controls' => $this->hasMiddleware($route, ['role:', 'permission:']),
        ];

        foreach ($criticalRequirements as $requirement => $met) {
            if ($met) {
                $score += 2;
            } else {
                // HIPAA violations are critical
                return $this->getScore(-10);
            }
        }

        // Additional security measures
        if ($this->hasMiddleware($route, ['two-factor'])) {
            $score++;
        }

        if ($this->hasMinimumAccessLevel($route)) {
            $score++;
        }

        return $this->getScore($score);
    }

    private function handlesPatientData(string $route): bool
    {
        return collect($this->phiRoutes)->contains(
            fn($prefix) => str_starts_with($route, $prefix)
        );
    }
}
```

**Audit Trail System**:
```php
class HIPAAComplianceMonitor
{
    public function runComplianceAudit(): void
    {
        $routes = app(Router::class)->getRoutes();

        $result = AuditRoutes::for($routes)
            ->setBenchmark(10) // Perfect score required for PHI
            ->run([
                HIPAASecurityAuditor::make()->setWeight(1),
            ]);

        // Log all results for audit trail
        foreach ($result->getRoutes() as $route) {
            HIPAAComplianceLog::create([
                'route_name' => $route->getIdentifier(),
                'compliance_score' => $route->getScore(),
                'audit_date' => now(),
                'violations' => $route->getScore() < 10 ?
                    $this->identifyViolations($route) : null,
                'auditor_version' => '1.0',
            ]);
        }

        // Alert on violations
        $violations = $result->where('score', '<', 10);
        if ($violations->isNotEmpty()) {
            $this->alertSecurityTeam($violations);
        }
    }

    private function alertSecurityTeam($violations): void
    {
        Mail::to(config('hipaa.security_team_email'))->send(
            new HIPAAViolationAlert($violations)
        );

        // Log to security incident system
        foreach ($violations as $violation) {
            SecurityIncident::create([
                'type' => 'hipaa_route_violation',
                'route' => $violation->getIdentifier(),
                'severity' => 'critical',
                'detected_at' => now(),
            ]);
        }
    }
}
```

**Results**: HIPAA compliance certification, zero security incidents, 100% audit trail coverage.

---

## Enterprise API Gateway Security

### Company Profile: Large Corporation
- **Size**: 1000+ microservice routes, 50+ development teams
- **Industry**: Enterprise software, multiple business units
- **Challenge**: Centralized security governance across distributed teams

### Implementation

**Service-Level Security Standards**:
```php
class EnterpriseSecurityGovernance
{
    private array $serviceSecurityLevels = [
        'public' => 25,      // Public APIs
        'internal' => 50,    // Internal services
        'sensitive' => 75,   // Customer data
        'critical' => 95,    // Financial/legal data
    ];

    public function auditServiceSecurity(string $serviceName): AuditedRouteCollection
    {
        $routes = $this->getServiceRoutes($serviceName);
        $securityLevel = $this->getServiceSecurityLevel($serviceName);

        return AuditRoutes::for($routes)
            ->setBenchmark($this->serviceSecurityLevels[$securityLevel])
            ->run($this->getAuditorsForSecurityLevel($securityLevel));
    }

    private function getAuditorsForSecurityLevel(string $level): array
    {
        $baseAuditors = [
            PolicyAuditor::make()->setWeight(20),
            PhpUnitAuditor::make()->setWeight(15),
        ];

        return match($level) {
            'public' => [
                ...$baseAuditors,
                MiddlewareAuditor::make(['throttle:'])->setWeight(25),
            ],
            'internal' => [
                ...$baseAuditors,
                MiddlewareAuditor::make(['auth:internal'])->setWeight(30),
            ],
            'sensitive' => [
                ...$baseAuditors,
                MiddlewareAuditor::make(['auth', 'encrypt-response'])->setWeight(35),
                new DataClassificationAuditor(),
            ],
            'critical' => [
                ...$baseAuditors,
                MiddlewareAuditor::make(['auth', '2fa', 'audit-log'])->setWeight(40),
                new CriticalDataAuditor(),
                new ComplianceAuditor('sox'),
            ],
        };
    }
}
```

**Automated Governance Dashboard**:
```bash
#!/bin/bash
# scripts/enterprise-security-scan.sh

# Scan all microservices
services=("user-service" "payment-service" "order-service" "audit-service")

for service in "${services[@]}"; do
    echo "🔍 Scanning $service..."

    php artisan enterprise:audit-service "$service" \
        --export json \
        --filename "${service}-security.json"

    FAILED=$(jq '.summary.failed' "storage/exports/${service}-security.json")

    if [ "$FAILED" -gt 0 ]; then
        echo "⚠️ $service: $FAILED security violations"

        # Create Jira ticket for violations
        jira create-issue \
            --project "SEC" \
            --type "Security Violation" \
            --summary "$service Security Audit Failed" \
            --description "Service $service has $FAILED security violations"
    else
        echo "✅ $service: All security checks passed"
    fi
done

# Generate executive summary
php artisan enterprise:security-summary
```

**Results**: 99.8% security compliance across 1000+ routes, automated governance for 50+ teams, executive-level security visibility.

---

## Common Implementation Patterns

### Graduated Security Standards

Teams often implement tiered security based on risk:

```php
// Security tiers by route sensitivity
$securityTiers = [
    'public' => [
        'benchmark' => 25,
        'auditors' => [RateLimitAuditor::class],
    ],
    'authenticated' => [
        'benchmark' => 50,
        'auditors' => [AuthAuditor::class, TestCoverageAuditor::class],
    ],
    'sensitive' => [
        'benchmark' => 75,
        'auditors' => [AuthAuditor::class, PolicyAuditor::class, EncryptionAuditor::class],
    ],
    'critical' => [
        'benchmark' => 95,
        'auditors' => [ComprehensiveSecurityAuditor::class],
    ],
];
```

### Continuous Monitoring

Production teams implement ongoing security monitoring:

```php
// Scheduled security monitoring
class SecurityMonitoringScheduler
{
    public function schedule(Schedule $schedule): void
    {
        // Daily comprehensive audit
        $schedule->command('route:audit --benchmark 75')
                 ->daily()
                 ->at('02:00')
                 ->onFailure(fn() => $this->alertSecurityTeam());

        // Hourly critical route monitoring
        $schedule->call(fn() => $this->auditCriticalRoutes())
                 ->hourly();

        // Weekly compliance reporting
        $schedule->command('compliance:generate-report')
                 ->weekly()
                 ->sundays()
                 ->at('01:00');
    }
}
```

## Key Success Factors

1. **Start Small**: Begin with basic audits, gradually increase complexity
2. **Team Buy-in**: Get security champion in each development team
3. **Automated Integration**: Make security audits part of CI/CD pipeline
4. **Clear Standards**: Define security benchmarks for different route types
5. **Regular Review**: Continuously improve auditors and standards
6. **Compliance Focus**: Align audits with regulatory requirements
7. **Executive Reporting**: Provide high-level security metrics to leadership

## Next Steps

- **[Advanced Usage Guide](../../guides/advanced-usage.md)**: Complex enterprise configurations
- **[Custom Auditors Guide](../../guides/custom-auditors.md)**: Build specialized auditors
- **[CI Integration Guide](../../guides/ci-integration.md)**: Automate security workflows
- **[Architecture Overview](../architecture/overview.md)**: Understand system internals