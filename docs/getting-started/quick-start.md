# Quick Start Guide

Get your Laravel application's routes audited for security vulnerabilities in under 5 minutes. This guide covers the essential steps to identify unprotected routes, missing authentication, and test coverage gaps.

## What You'll Accomplish

By the end of this guide, you'll be able to:
- Run comprehensive route security audits
- Identify routes lacking authentication or authorization
- Check which routes need test coverage
- Export audit results for team review

## Installation

Install the package via Composer in your Laravel project:

```bash
composer require mydevnl/audit-routes --dev
```

The package will auto-register with Laravel's service container.

## Your First Audit

### 1. Basic Route Audit

Run a comprehensive audit of all your application routes:

```bash
php artisan route:audit -vv
```

**Expected Output:**
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        admin.dashboard                      -50
  ✖        api.orders.store                     0
  ✓        user.index                           100
 -------- ------------------------------------ --------

[ERROR] 2/3 routes scored below the benchmark
```

Routes with negative or low scores need immediate attention for security issues.

### 2. Generate HTML Report

Create a detailed, shareable audit report:

```bash
php artisan route:audit-report
```

This generates an HTML file at `storage/exports/audit-routes/index.html` with:
- Visual dashboard of route security status
- Detailed breakdown of each route's vulnerabilities
- Prioritized overview for improving route security

### 3. Focus on Specific Issues

#### Check Authentication Coverage
```bash
php artisan route:audit-auth --export html --filename auth-report.html -vv
```

#### Verify Test Coverage
```bash
php artisan route:audit-test-coverage --benchmark 1 -vv
```

## Understanding the Results

### Scoring System
- **Positive scores**: Routes with proper security measures
- **Zero scores**: Routes with either mixed or ignored results
- **Negative scores**: Routes with security vulnerabilities

### Common Issues Found
- **Missing Authentication**: Routes accessible without login
- **No Authorization**: Routes lacking policy/permission checks
- **Untested Routes**: Routes without corresponding test coverage
- **Missing Middleware**: Routes lacking essential security middleware

### Priority Actions
1. **Negative scores**: Immediate security fixes required
2. **Zero scores**: Review and implement missing protections
3. **Low positive scores**: Enhance security measures

## Next Steps

Now that you've completed your first audit, explore these advanced features:

- **[Configuration](configuration.md)**: Customize audit settings and ignored routes
- **[Basic Usage Guide](../guides/basic-usage.md)**: Learn detailed auditing techniques
- **[Testing Guide](../guides/testing.md)**: PHPUnit assertions and test suite integration
- **[Custom Auditors](../guides/custom-auditors.md)**: Build application-specific security checks
- **[CI Integration](../guides/ci-integration.md)**: Automate audits in your deployment pipeline

## Troubleshooting

**No routes found?**
- Ensure your Laravel application has defined routes
- Check that route caching hasn't excluded routes: `php artisan route:clear`

**Permission errors on report generation?**
- Verify `storage/exports/audit-routes/` directory is writable
- Check Laravel storage permissions: `chmod -R 775 storage/`

For more detailed troubleshooting, see our [Troubleshooting Guide](../guides/troubleshooting.md).