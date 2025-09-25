# Quick Reference

Fast reference for common Audit Routes patterns and commands.

## Commands

### Basic Auditing
```bash
# Basic audit
php artisan route:audit

# Detailed output
php artisan route:audit -vv

# Custom benchmark
php artisan route:audit --benchmark 50 -vv
```

### Specialized Audits
```bash
# Authentication analysis
php artisan route:audit-auth -vv

# Test coverage check
php artisan route:audit-test-coverage --benchmark 1 -vv

# Comprehensive HTML report
php artisan route:audit-report
```

### Export Options
```bash
# HTML export
php artisan route:audit --export html --filename security-report.html

# JSON export for CI/CD
php artisan route:audit --benchmark 50 --export json --filename audit.json
```

## Programmatic Usage

### Basic Setup
```php
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;

$result = AuditRoutes::for($router->getRoutes())
    ->setBenchmark(50)
    ->run([
        PolicyAuditor::make()->setWeight(25),
        MiddlewareAuditor::make(['auth'])->setWeight(20),
    ]);
```

### Common Auditor Patterns
```php
// Authentication required
MiddlewareAuditor::make(['auth'])->setWeight(25)

// Policy-based authorization
PolicyAuditor::make()->setWeight(30)

// Test coverage validation
PhpUnitAuditor::make()->setWeight(20)->setLimit(100)

// Conditional application
MiddlewareAuditor::make(['admin'])
    ->when(fn($route) => str_starts_with($route->getIdentifier(), 'admin.'))
```

## Common Benchmarks

| Value | Level | Use Case |
|-------|-------|----------|
| **0** | Minimal | Development, flag only vulnerabilities |
| **1** | Testing | Require basic test coverage |
| **25** | Basic | New projects, basic security |
| **50** | Standard | Production applications |
| **75** | High | Sensitive data applications |
| **85+** | Critical | Financial, healthcare systems |

## PHPUnit Assertions

### Basic Assertions
```php
use MyDev\AuditRoutes\Testing\AssertsAuditRoutes;

class SecurityTest extends TestCase
{
    use AssertsAuditRoutes;

    public function test_all_routes_are_tested()
    {
        $this->assertRoutesAreTested(['*']);
    }

    public function test_admin_routes_have_auth()
    {
        $this->assertRoutesHaveMiddleware(['admin.*'], ['auth']);
    }
}
```

### Custom Auditor Assertions
```php
public function test_routes_pass_security_audit()
{
    $this->assertAuditRoutesOk(['*'], [
        PolicyAuditor::make()->setWeight(25),
        MiddlewareAuditor::make(['auth'])->setWeight(20)
    ], benchmark: 25);
}
```

## Configuration Snippets

### Environment-Specific
```php
// config/audit-routes.php
return [
    'benchmark' => env('AUDIT_BENCHMARK', match(env('APP_ENV')) {
        'production' => 75,
        'staging' => 50,
        'local' => 25,
        default => 0
    }),
    'ignored-routes' => [
        'telescope*',
        'debugbar.*',
        'ignition.*',
    ],
];
```

### CI/CD Integration
```yaml
# .github/workflows/security.yml
- name: Run Security Audit
  run: |
    php artisan route:audit --benchmark 50 --export json --filename security.json

- name: Check Results
  run: |
    if jq '.summary.failed > 0' security.json; then
      echo "Security audit failed"
      exit 1
    fi
```

## Troubleshooting

### Common Issues
```bash
# No routes found
php artisan route:clear
php artisan route:list

# Permission errors
chmod -R 775 storage/
sudo chown -R $USER:www-data storage/

# Configuration issues
php artisan config:clear
php artisan vendor:publish --tag=audit-routes-config
```

### Debug Commands
```bash
# Full diagnostic output
php artisan route:audit --benchmark 0 -vv

# Test specific route
php artisan route:audit --pattern="admin.*" -vv

# Check middleware detection
php artisan route:audit-auth -vv
```

## Related Documentation

- **[Installation](getting-started/installation.md)** - Setup and requirements
- **[Basic Usage](guides/basic-usage.md)** - Detailed examples and patterns
- **[Testing](guides/testing.md)** - PHPUnit integration
- **[Configuration](getting-started/configuration.md)** - Settings and customization
- **[Custom Auditors](guides/custom-auditors.md)** - Build your own auditors
- **[CI Integration](guides/ci-integration.md)** - Automated security checks
- **[Troubleshooting](guides/troubleshooting.md)** - Common issues and solutions