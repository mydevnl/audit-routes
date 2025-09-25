# Frequently Asked Questions

Common questions about Audit Routes usage, troubleshooting, and best practices.

## General Questions

### What is Audit Routes?

Audit Routes is a Laravel package that analyzes your application's routes for security vulnerabilities, test coverage gaps, and compliance issues. It provides automated route security auditing with customizable scoring and reporting.

### Why do I need route security auditing?

Route security auditing helps identify:
- **Unprotected endpoints** that should require authentication
- **Missing authorization** on sensitive routes
- **Untested routes** vulnerable to regressions
- **Compliance violations** with security standards
- **Inconsistent security patterns** across your application

### Is Audit Routes suitable for production use?

Audit Routes is designed as a development and CI/CD tool. While it can run in production, it's typically used for:
- Development security validation
- CI/CD pipeline gates
- Security compliance reporting
- Code review assistance

## Installation and Setup

### Can I use Audit Routes with older Laravel versions?

Audit Routes supports Laravel 7.0 through 11.0. For older versions:
- **Laravel 5.x**: Not officially supported, but may work with manual service provider registration
- **Laravel 6.x**: Limited compatibility, consider upgrading Laravel

### The commands aren't available after installation. What's wrong?

Common solutions:
1. Clear caches: `php artisan config:clear && php artisan cache:clear`
2. Regenerate autoload: `composer dump-autoload`
3. Manual registration in `config/app.php` if auto-discovery fails
4. Verify Laravel version compatibility

### How do I configure different settings for different environments?

Use environment variables in your configuration:

```php
// config/audit-routes.php
return [
    'benchmark' => env('AUDIT_BENCHMARK', 0),
    'ignored-routes' => env('APP_ENV') === 'production' ? [] : [
        'telescope*', 'debugbar.*'
    ],
];
```

Set in `.env`:
```env
# Development
AUDIT_BENCHMARK=25

# Production
AUDIT_BENCHMARK=75
```

## Usage Questions

### What's a good benchmark score to start with?

Recommended benchmarks by experience level:
- **New to security**: Start with `0` (flag negative scores only)
- **Basic security**: Use `25-35`
- **Moderate security**: Use `50-60`
- **High security**: Use `75-85`
- **Enterprise/compliance**: Use `90+`

Gradually increase as your application security improves.

### Why are some routes showing negative scores?

Negative scores indicate security violations:
- **Missing authentication** on protected routes
- **Lack of authorization** on sensitive endpoints
- **Absent rate limiting** on API routes
- **Missing test coverage** for critical functionality

Use `php artisan route:audit -vv` to see detailed breakdown.

### How do I exclude development/debug routes?

Add patterns to your configuration:

```php
// config/audit-routes.php
'ignored-routes' => [
    'telescope*',      // Laravel Telescope
    'debugbar.*',      // Laravel Debugbar
    'ignition.*',      // Ignition error pages
    'horizon*',        // Laravel Horizon
    '_dusk/*',         // Laravel Dusk
    'local.*',         // Local development routes
],
```

### Can I audit only specific routes?

Yes, use route filtering:

```bash
# Audit only admin routes
php artisan route:audit --pattern="admin.*"

# Audit specific commands
php artisan route:audit-auth  # Authentication only
php artisan route:audit-test-coverage  # Test coverage only
```

## Test Coverage Issues

### Tests exist but aren't being detected. Why?

Common causes:
1. **Wrong test directory**: Verify `tests.directory` in configuration
2. **Route names vs URLs**: Use `route('name')` instead of `/url` in tests
3. **Method names**: Ensure using configured `acting-methods`
4. **Base test class**: Check `tests.implementation` matches your setup

### How does test coverage detection work?

Audit Routes uses AST (Abstract Syntax Tree) parsing to:
1. Parse all PHP files in your test directory
2. Find test methods using configured HTTP methods (`get`, `post`, etc.)
3. Extract route calls from test code
4. Match route names with actual route definitions
5. Count occurrences per route

### Can I customize which test methods are recognized?

Yes, configure `acting-methods`:

```php
// config/audit-routes.php
'tests' => [
    'acting-methods' => [
        'get', 'post', 'put', 'patch', 'delete',
        'getJson', 'postJson', 'putJson',
        'actingAs', 'withHeaders',
        'customTestMethod',  // Your custom helper
    ],
],
```

## Middleware and Security

### How does middleware detection work?

Audit Routes analyzes Laravel's route middleware stack:
- Extracts middleware names and parameters
- Checks for patterns like `auth`, `can:permission`, `throttle:60,1`
- Validates middleware presence against requirements
- Supports both string and class-based middleware

### My routes have middleware but auditors don't detect it. What's wrong?

Check these common issues:
1. **Middleware names**: Use exact names (`auth`, not `authenticate`)
2. **Route groups**: Ensure middleware is properly applied to route groups
3. **Custom middleware**: Add custom middleware patterns to auditors
4. **Route caching**: Clear route cache with `php artisan route:clear`

### How do I audit custom middleware?

Create a custom auditor or configure `MiddlewareAuditor`:

```php
// Custom MiddlewareAuditor
$result = AuditRoutes::for($routes)->run([
    MiddlewareAuditor::make([
        'auth',              // Standard Laravel auth
        'custom-security',   // Your custom middleware
        'api-throttle',      // Custom rate limiting
    ])->setWeight(20)
]);
```

## Performance and Scaling

### Audits are slow on large applications. How can I optimize?

Performance optimization strategies:
1. **Use focused audits**: Run specific auditors instead of comprehensive
2. **Limit test parsing**: Configure specific test directories
3. **Increase memory**: `php -d memory_limit=512M artisan route:audit`
4. **Cache results**: Implement caching for repeated audits
5. **Parallel processing**: Use background job queues for large audits

### How much memory does Audit Routes use?

Memory usage depends on:
- **Route count**: ~1KB per route
- **Test files**: ~50KB per test file during parsing
- **AST parsing**: ~10MB baseline for PHP-Parser

For large applications (1000+ routes), allocate at least 512MB memory.

### Can I run audits in parallel?

Yes, several approaches:
1. **Split by auditor type**: Run different audit commands separately
2. **Route segmentation**: Audit route groups independently
3. **Background jobs**: Queue audits for large applications
4. **CI parallelization**: Use CI matrix builds for different audit types

## CI/CD Integration

### How do I fail deployments on security violations?

Configure CI to check audit exit codes:

```bash
#!/bin/bash
php artisan route:audit --benchmark 50

if [ $? -ne 0 ]; then
    echo "❌ Security audit failed - blocking deployment"
    exit 1
fi
```

### Should I run audits on every commit?

Recommended approach:
- **Pre-commit hooks**: Light security checks (low benchmark)
- **Pull requests**: Comprehensive audits with reporting
- **Main branch**: Full security suite with high benchmarks
- **Scheduled**: Weekly comprehensive compliance reports

### How do I generate reports for stakeholders?

Use HTML exports and automated distribution:

```bash
# Generate comprehensive report
php artisan route:audit-report

# Upload to shared location
aws s3 cp storage/exports/audit-routes/ s3://reports/security/ --recursive

# Email stakeholders
php artisan security:email-report --recipients=security-team@company.com
```

## Customization and Extension

### Can I create custom auditors for my specific needs?

Yes! Implement the `AuditorInterface`:

```php
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class CustomSecurityAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        // Your custom security logic
        $isCompliant = $this->checkCustomRequirements($route);
        return $this->getScore($isCompliant ? 1 : 0);
    }
}
```

### How do I integrate with third-party security tools?

Export results to JSON and integrate:

```bash
# Export results
php artisan route:audit --export json --filename security-data.json

# Process with external tools
python security-analyzer.py storage/exports/audit-routes/security-data.json

# Send to SIEM
curl -X POST https://siem.company.com/api/security-events \
     -d @storage/exports/audit-routes/security-data.json
```

### Can I modify the scoring system?

Yes, through custom auditors and weights:

```php
// Custom scoring logic
$result = AuditRoutes::for($routes)->run([
    PolicyAuditor::make()->setWeight(50),      // High weight for authorization
    PhpUnitAuditor::make()->setWeight(25),     // Medium weight for tests
    CustomAuditor::make()->setWeight(100),     // Highest weight for custom logic
]);
```

## Troubleshooting

### I'm getting "Class not found" errors

Solutions:
1. Run `composer dump-autoload`
2. Check namespace imports in custom auditors
3. Verify Laravel's service provider registration
4. Clear configuration cache

### Audit results are inconsistent between runs

Check for:
1. **Route caching**: Clear with `php artisan route:clear`
2. **File changes**: Test files modified between runs
3. **Configuration drift**: Environment variables changing
4. **Random test data**: Ensure deterministic test scenarios

### Memory exhausted errors during audits

Solutions:
1. **Increase memory limit**: `php -d memory_limit=1G`
2. **Process in chunks**: Audit route subsets separately
3. **Optimize test directory**: Remove large/irrelevant test files
4. **Use streaming**: Process results without loading all in memory

## Best Practices

### What security standards should I follow?

Consider these frameworks:
- **OWASP Top 10**: Web application security risks
- **Laravel Security Best Practices**: Framework-specific guidelines
- **Industry standards**: PCI DSS, HIPAA, SOX depending on your domain
- **Company policies**: Internal security requirements

### How often should I run security audits?

Recommended frequency:
- **Development**: Every commit (light checks)
- **Pull requests**: Comprehensive audits
- **Releases**: Full security validation
- **Production monitoring**: Monthly compliance reports
- **Incident response**: After security incidents

### Should I commit audit reports to version control?

**Don't commit reports** because:
- Reports contain sensitive route information
- Files are large and change frequently
- Better suited for CI artifacts

**Do commit**:
- Configuration files
- Custom auditors
- CI/CD workflows

## Getting Help

### Where can I find more examples?

Check these resources:
- **Package documentation**: Complete guides and examples
- **GitHub repository**: Real-world usage examples
- **Test files**: Package tests show usage patterns
- **Community discussions**: GitHub Discussions section

### How do I report bugs or request features?

1. **Search existing issues**: Check if already reported
2. **Provide details**: Laravel version, PHP version, error messages
3. **Include examples**: Minimal reproduction case
4. **Follow templates**: Use issue templates when available

### Is commercial support available?

Contact the maintainer for:
- **Enterprise consulting**: Custom auditor development
- **Training sessions**: Team security education
- **Priority support**: Faster issue resolution
- **Custom integrations**: Tailored implementations

## Next Steps

- **[Quick Start Guide](../getting-started/quick-start.md)**: Begin using Audit Routes
- **[Troubleshooting Guide](../guides/troubleshooting.md)**: Resolve specific issues
- **[Advanced Usage](../guides/advanced-usage.md)**: Complex configurations
- **[Custom Auditors](../guides/custom-auditors.md)**: Build specialized auditors