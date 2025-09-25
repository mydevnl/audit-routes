# Configuration Reference

Customize Audit Routes behavior to match your application's structure and security requirements. Configuration options control route filtering, test discovery, output formatting, and scoring thresholds.

## Publishing Configuration

Publish the configuration file to customize default settings:

```bash
php artisan vendor:publish --tag=audit-routes-config
```

This creates `config/audit-routes.php` in your Laravel application.

## Configuration Options

### Route Filtering

#### ignored-routes
**Type**: `array`
**Default**: `['telescope*', 'debugbar.*', 'ignition.*', 'sanctum.*']`

Routes to exclude from all audits using Laravel route name patterns.

**Example**:
```php
'ignored-routes' => [
    'telescope*',        // All Telescope routes
    'debugbar.*',        // Laravel Debugbar routes
    'ignition.*',        // Ignition error page routes
    'sanctum.*',         // Laravel Sanctum routes
    'admin.logs.*',      // Custom admin log routes
    'api.webhooks.*',    // Third-party webhook handlers
],
```

**Pattern Matching**:
- `*`: Matches any characters (wildcard)
- `.*`: Matches any route segment
- `exact.route.name`: Exact route name match

---

### Scoring System

#### benchmark
**Type**: `int`
**Default**: `0`

Minimum score threshold for route compliance. Routes below this score are flagged as security risks.

**Example**:
```php
'benchmark' => 50,  // Routes scoring below 50 need attention
```

**Choosing Benchmark Values**:

| Value | Security Level | Use Case | Description |
|-------|---------------|----------|-------------|
| **0** | Minimal | Development, Legacy apps | Flag only routes with security vulnerabilities (negative scores) |
| **1** | Test Coverage | Test validation | Require at least 1 test per route |
| **25** | Basic | New projects, Staging | Basic security measures required |
| **50** | Standard | Production apps | Balanced security and practicality |
| **75** | High | Sensitive data apps | Strong security posture required |
| **85-95** | Critical | Financial, Healthcare | Maximum security for regulated industries |

### Test Configuration

#### tests.directory
**Type**: `string`
**Default**: `'tests'`

Directory containing PHPUnit test files for coverage analysis.

**Example**:
```php
'tests' => [
    'directory' => 'tests',  // Laravel default
    // Or for custom structures:
    'directory' => 'app/Tests',
    'directory' => 'src/Tests',
],
```

#### tests.implementation
**Type**: `string`
**Default**: `\Tests\TestCase::class`

Base test class used by your test suite for proper test method detection.

**Example**:
```php
'tests' => [
    'implementation' => \Tests\TestCase::class,              // Laravel default
    'implementation' => \App\Tests\BaseTestCase::class,      // Custom base class
    'implementation' => \PHPUnit\Framework\TestCase::class,  // Pure PHPUnit
],
```

#### tests.acting-methods
**Type**: `array`
**Default**: `['get', 'getJson', 'post', 'postJson', 'put', 'putJson', 'patch', 'patchJson', 'delete', 'deleteJson', 'call', 'json']`

HTTP test methods to recognize when analyzing test coverage.

**Example**:
```php
'tests' => [
    'acting-methods' => [
        'get', 'post', 'put', 'patch', 'delete',  // Basic HTTP methods
        'getJson', 'postJson',                     // JSON API methods
        'actingAs',                                // Authentication helper
        'withHeaders',                             // Custom headers
        'call',                                    // Generic method
    ],
],
```

**Custom Methods**: Add methods from testing packages or custom helpers.

---

### Output Configuration

The route auditor supports multiple output formats to suit different use cases:

- **Verbose Console Output** – Detailed audit results printed directly to the terminal
- **HTML Export** – Generate styled, shareable audit reports
- **JSON Export** – Easily consume audit data in tools or scripts
- **Custom Benchmarks** – Define and include application-specific metrics

You can also extend the system using **data aggregators**, which let you plug in custom logic to enrich reports with detailed, context-aware insights.

#### output.directory
**Type**: `string`
**Default**: `'storage/exports/audit-routes'`

Directory for generated audit reports and exports.

**Example**:
```php
'output' => [
    'directory' => 'storage/exports/audit-routes',  // Default
    'directory' => 'storage/reports',               // Custom location
    'directory' => 'public/audits',                 // Public access
],
```

**Permissions**: Ensure the directory is writable by the web server.

#### Export Formats

**HTML Reports**
Generate comprehensive, styled reports perfect for stakeholder review:

```bash
# Generate HTML export with custom filename
php artisan route:audit --export html --filename security-report.html

# Generate comprehensive HTML dashboard
php artisan route:audit-report
```

HTML reports include:
- Visual dashboard of route security status
- Detailed breakdown of each route's vulnerabilities
- Interactive filtering and sorting capabilities
- Shareable format for team collaboration

**JSON Exports**
Machine-readable format ideal for integration with other tools:

```bash
# Export audit results as JSON
php artisan route:audit --export json --filename audit-results.json

# JSON export with custom benchmark
php artisan route:audit --benchmark 50 --export json
```

JSON exports are perfect for:
- CI/CD pipeline integration
- Custom reporting tools
- Automated processing and analysis
- Data visualization tools

#### output.html-index-template
**Type**: `string`
**Default**: `'audit-routes::output.index'`

Blade template for HTML report index pages.

**Example**:
```php
'output' => [
    'html-index-template' => 'audit-routes::output.index',  // Package default
    'html-index-template' => 'custom.audit.index',          // Custom template
],
```

#### output.html-report-template
**Type**: `string`
**Default**: `'audit-routes::output.report'`

Blade template for individual HTML reports.

**Example**:
```php
'output' => [
    'html-report-template' => 'audit-routes::output.report',  // Package default
    'html-report-template' => 'custom.audit.report',          // Custom template
],
```

## Environment-Specific Configuration

### Development Environment

```php
return [
    'ignored-routes' => [
        'telescope*', 'debugbar.*', 'ignition.*',
        'sanctum.*', '_dusk.*',  // Add Dusk routes
    ],
    'benchmark' => 25,  // Relaxed for development
    'tests' => [
        'directory' => 'tests',
        'acting-methods' => [
            'get', 'post', 'getJson', 'postJson',
            'actingAs', 'withoutMiddleware',  // Dev helpers
        ],
    ],
];
```

### Production Environment

```php
return [
    'ignored-routes' => [
        'sanctum.*',  // Only essential exclusions
    ],
    'benchmark' => 75,  // Strict security standards
    'tests' => [
        'directory' => 'tests',
        'acting-methods' => [
            'get', 'getJson', 'post', 'postJson',
            'put', 'putJson', 'patch', 'patchJson',
            'delete', 'deleteJson',
        ],
    ],
];
```

## Command-Line Overrides

Most configuration options can be overridden via command-line flags:

```bash
# Override benchmark threshold
php artisan route:audit --benchmark 100

# Custom export location
php artisan route:audit-report --filename custom-report.html

# Verbose output regardless of config
php artisan route:audit -vv
```

## Validation

The package validates configuration options on load:

**Common Issues**:
- **Invalid directory**: Ensure test directory exists and is readable
- **Missing base class**: Verify `tests.implementation` class exists
- **Permission errors**: Check output directory is writable
- **Invalid patterns**: Route ignore patterns must be valid strings


## Next Steps

- **[Commands Reference](../reference/api/commands.md)**: Learn command-line options
- **[Basic Usage Guide](../guides/basic-usage.md)**: Apply configuration in practice
- **[Quick Start](quick-start.md)**: Get started with default settings
- **[Advanced Usage](../guides/advanced-usage.md)**: Complex configuration scenarios