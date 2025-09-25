# Audit Routes

This PHP Package provides a streamlined approach to gaining insights into the security and protection of your application's routes. In just a few seconds, you can assess critical aspects such as:

- **Test Coverage:** Comprehensive tests cover all routes to ensure reliability
- **Authentication:** Routes requiring authentication are clearly identified
- **Scoped Bindings:** Nested route models are scoped to maintain data integrity
- **Permissions:** Permission or policy checks enforce access control
- **Middleware:** Essential middleware is applied for security and request handling

[![Latest Stable Version](https://poser.pugx.org/mydevnl/audit-routes/v/stable)](https://packagist.org/packages/mydevnl/audit-routes)
[![Total Downloads](https://poser.pugx.org/mydevnl/audit-routes/downloads)](https://packagist.org/packages/mydevnl/audit-routes)
[![Coding standards](https://github.com/mydevnl/audit-routes/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/mydevnl/audit-routes/actions/workflows/coding-standards.yml)
[![Tests](https://github.com/mydevnl/audit-routes/actions/workflows/run-tests.yml/badge.svg)](https://github.com/mydevnl/audit-routes/actions/workflows/run-tests.yml)
[![License](https://poser.pugx.org/mydevnl/audit-routes/license)](https://packagist.org/packages/mydevnl/audit-routes)

Built for Laravel with extensible architecture for other PHP frameworks.

## Requirements

- **PHP**: 8.1+

## Documentation

Comprehensive documentation is available to help you get the most out of Audit Routes:

### Getting Started
- **[Installation](docs/getting-started/installation.md)** - Install and set up the package
- **[Quick Start](docs/getting-started/quick-start.md)** - Get auditing in under 5 minutes
- **[Configuration](docs/getting-started/configuration.md)** - Customize settings and behavior

### Guides
- **[Basic Usage](docs/guides/basic-usage.md)** - Essential patterns and common scenarios
- **[Advanced Usage](docs/guides/advanced-usage.md)** - Complex configurations and custom scoring
- **[Custom Auditors](docs/guides/custom-auditors.md)** - Build application-specific security checks
- **[Testing](docs/guides/testing.md)** - PHPUnit assertions and CI integration
- **[CI Integration](docs/guides/ci-integration.md)** - Automate audits in your deployment pipeline
- **[Troubleshooting](docs/guides/troubleshooting.md)** - Resolve common issues

### Reference
- **API Documentation**
  - **[Assertions](docs/reference/api/assertions.md)** - Integrate route security validation directly into your test suite
  - **[Auditors](docs/reference/api/auditors.md)** - Auditors are the core components that analyze your routes
  - **[Commands](docs/reference/api/commands.md)** - Available Artisan Commands
- **Architecture**
  - **[Auditor system](docs/reference/architecture/auditor-system.md)** - How the auditor system works internally
  - **[Overview](docs/reference/architecture/overview.md)** - Understanding the internal architecture
- **Examples**
  - **[Integrations](docs/reference/examples/integrations.md)** -  Integrating with popular PHP frameworks
  - **[Real world](docs/reference/examples/real-world.md)** -  Real-world implementation examples

### Community
- **[FAQ](docs/community/faq.md)** - Frequently asked questions
- **[Resources](docs/community/resources.md)** - Additional tools and resources

### Quick Reference
- **[Quick Reference](docs/quick-reference.md)** - Fast reference for commands and common patterns

## Installation

You can install the package via Composer:

```bash
composer require mydevnl/audit-routes --dev
```

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag=audit-routes-config
```

## Quick Start

Get your first audit running in seconds:

```bash
# Run a basic security audit
php artisan route:audit -vv

# Generate a detailed HTML report
php artisan route:audit-report

# Check authentication coverage
php artisan route:audit-auth -vv
```

For programmatic usage:

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

## How It Works

Audit Routes uses a **scoring system** to evaluate route security:

- **Built-in Auditors**: PolicyAuditor, MiddlewareAuditor, PhpUnitAuditor, and more
- **Configurable Weights**: Customize importance of different security aspects
- **Benchmark System**: Set minimum scores for compliance (routes below benchmark are flagged)
- **Multiple Outputs**: Console, HTML reports, JSON exports for different workflows

Learn more about the [Architecture](docs/reference/architecture/overview.md) and [Auditor System](docs/reference/architecture/auditor-system.md).

## Available Commands

The package provides several built-in commands to help you get started quickly:

- **`route:audit`** - Comprehensive route security analysis
- **`route:audit-report`** - Generate detailed HTML audit reports
- **`route:audit-test-coverage`** - Analyze test coverage for routes
- **`route:audit-auth`** - Focus on authentication middleware analysis

For detailed usage examples and command-line options, see the [Basic Usage Guide](docs/guides/basic-usage.md#command-line-options).

### Quick Reference

```bash
# Basic audit with detailed output
php artisan route:audit -vv

# High security standards
php artisan route:audit --benchmark 75 -vv

# Generate HTML report
php artisan route:audit-report

# Check authentication coverage
php artisan route:audit-auth -vv

# Verify test coverage
php artisan route:audit-test-coverage --benchmark 1 -vv

# Export results for CI/CD
php artisan route:audit --benchmark 50 --export json --filename security-audit.json
```

## Testing Integration

The package includes PHPUnit assertions for integrating route security checks directly into your test suite. Use the `AssertsAuditRoutes` trait to enforce security standards as part of your CI/CD pipeline.

See the [Testing Guide](docs/guides/testing.md) for comprehensive examples and best practices.

## Troubleshooting

**Common issues:**
- **No routes found?** Ensure your Laravel application has defined routes and clear route cache: `php artisan route:clear`
- **Permission errors?** Check that `storage/exports/audit-routes/` is writable: `chmod -R 775 storage/`
- **Configuration issues?** Verify your `config/audit-routes.php` settings match your project structure

For detailed troubleshooting and solutions, see the [Troubleshooting Guide](docs/guides/troubleshooting.md) or [FAQ](docs/community/faq.md).

## Contributing

We welcome contributions to this project! If you have ideas for improvements or find bugs, please submit them as issues on GitHub. We highly appreciate and encourage community participation.

For additional help or questions, feel free to reach out via GitHub issues.

Learn more about [contributing](CONTRIBUTING.md).

## Security Vulnerabilities

If you discover any security vulnerabilities, please report them immediately. All security-related issues will be addressed with the highest priority.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## We're still in development

Please be aware that the most stable release is an beta release and may be unstable.
The roadmap will be published soon. Follow [mydevnl](https://github.com/mydevnl) to stay updated!

May your routes be flawless! 🔒✨
