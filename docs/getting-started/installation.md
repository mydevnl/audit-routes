# Installation Guide

Comprehensive installation instructions for Audit Routes including system requirements, framework compatibility, and deployment considerations.

## System Requirements

### Minimum Requirements
- **PHP**: 8.1 or higher
- **Laravel**: 7.0 through 12.0

### Recommended Environment
- **PHP**: 8.2+ for optimal performance
- **Laravel**: 10.0+ for latest features
- **Composer**: 2.0+ for dependency resolution

### PHP Extensions
Required extensions (typically included in most PHP installations):
- `json` - JSON parsing for configuration and exports
- `mbstring` - String handling for route analysis
- `tokenizer` - AST parsing for test coverage analysis

## Composer Installation

Install via Composer in your Laravel project:

```bash
composer require mydevnl/audit-routes --dev
```

**Why `--dev` flag?**
Route auditing is typically used during development and CI/CD processes, not in production runtime.

## Framework-Specific Installation

### Laravel Auto-Discovery

Laravel 5.5+ automatically discovers the package. Verify installation:

```bash
php artisan --help | grep "route:audit"
```

Expected output:
```
route:audit              Comprehensive route security audit
route:audit-auth         Authentication middleware analysis
route:audit-report       Generate complete security report
route:audit-test-coverage PHPUnit test coverage analysis
```

### Manual Laravel Registration

For Laravel versions without auto-discovery or manual control:

```php
// config/app.php
'providers' => [
    // ...
    MyDev\AuditRoutes\AuditRoutesServiceProvider::class,
],
```

### Lumen Installation

Install for Lumen framework:

1. **Install package**:
   ```bash
   composer require mydevnl/audit-routes --dev
   ```

2. **Register service provider** in `bootstrap/app.php`:
   ```php
   $app->register(MyDev\AuditRoutes\AuditRoutesServiceProvider::class);
   ```

3. **Enable facades** (if needed):
   ```php
   $app->withFacades();
   ```

4. **Publish configuration**:
   ```bash
   php artisan vendor:publish --tag=audit-routes-config
   ```

   Or manually copy:
   ```bash
   cp vendor/mydevnl/audit-routes/config/audit-routes.php config/
   ```

## Configuration Setup

### Publish Configuration File

Generate the configuration file for customization:

```bash
php artisan vendor:publish --tag=audit-routes-config
```

This creates `config/audit-routes.php` with default settings.

### Verify Configuration

Check that configuration loads correctly:

```bash
php artisan config:show audit-routes
```

Expected output showing configuration structure:
```php
[
    "ignored-routes" => [
        "telescope*",
        "debugbar.*",
        // ...
    ],
    "benchmark" => 0,
    "tests" => [
        "directory" => "tests",
        // ...
    ]
]
```

## Verification and Testing

### Basic Functionality Test

Verify the package works correctly:

```bash
php artisan route:audit --help
```

Run a basic audit:
```bash
php artisan route:audit -vv
```

Generate an audit report:
```bash
php artisan route:audit-report -vv
```

### Permission Verification

Check file system permissions for exports:

```bash
# Create export directory
mkdir -p storage/exports/audit-routes

# Set permissions
chmod -R 775 storage/exports/audit-routes

# Test HTML export
php artisan route:audit --export html --filename test-install.html

# Verify file was created
ls -la storage/exports/audit-routes/
```

## Common Installation Issues

### Command Not Found

**Problem**: `Command "route:audit" is not defined`

**Solutions**:
1. **Clear caches**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Verify installation**:
   ```bash
   composer show mydevnl/audit-routes
   ```

3. **Regenerate autoload**:
   ```bash
   composer dump-autoload
   ```

4. **Manual registration** (if auto-discovery fails):
   ```php
   // config/app.php
   'providers' => [
       MyDev\AuditRoutes\AuditRoutesServiceProvider::class,
   ],
   ```

### Docker Environment

For Docker-based development:

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install required PHP extensions
RUN docker-php-ext-install json tokenizer mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install application dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader
```

```yaml
# docker-compose.yml
services:
  app:
    build: .
    volumes:
      - ./storage/exports:/app/storage/exports
    environment:
      - AUDIT_BENCHMARK=50
```

## Production Considerations

### Security

- **Never install in production** unless specifically needed for production auditing
- **Use `--dev` flag** to keep as development dependency
- **Secure export directories** if running audits in production

### Performance

- **Use focused auditors** rather than comprehensive audits
- **Implement caching** for repeated audit runs
- **Monitor memory usage** on large applications

### Deployment

CI/CD pipeline integration:

```yaml
# .github/workflows/security-audit.yml
name: Security Audit

on: [push, pull_request]

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: json, tokenizer, mbstring

      - name: Install dependencies
        run: composer install --dev --no-interaction

      - name: Run security audit
        run: php artisan route:audit --benchmark 50
```

## Updating and Maintenance

### Version Updates

Check for updates:
```bash
composer show mydevnl/audit-routes
composer outdated mydevnl/audit-routes
```

Update to latest version:
```bash
composer update mydevnl/audit-routes
```

### Configuration Migration

When updating, compare configuration changes:
```bash
php artisan vendor:publish --tag=audit-routes-config --force
```

Review and merge any new configuration options.

## Next Steps

- **[Quick Start Guide](quick-start.md)**: Get started with your first audit
- **[Configuration Guide](configuration.md)**: Customize settings for your environment
- **[Basic Usage Guide](../guides/basic-usage.md)**: Learn essential audit patterns
- **[Troubleshooting Guide](../guides/troubleshooting.md)**: Resolve common issues