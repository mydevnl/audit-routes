# Artisan Commands API Reference

Audit Routes provides several Artisan commands for analyzing your Laravel application's route security. Each command focuses on specific security aspects and supports multiple output formats.

## Core Commands

### route:audit

Comprehensive route security audit using customizable auditors and scoring.

**Purpose**: Run flexible route audits with custom auditor configurations and scoring thresholds.

**Signature**: `route:audit {--benchmark=0} {--export=} {--filename=} {-v|vv|vvv}`

**Options**:
- `--benchmark=N`: Minimum score threshold (default: 0)
- `--export=FORMAT`: Export format (html, json)
- `--filename=NAME`: Custom export filename
- `-vv`: Verbose output with detailed scoring

**Usage Examples**:
```bash
# Basic comprehensive audit
php artisan route:audit -vv

# Set high security benchmark
php artisan route:audit --benchmark 75 -vv

# Generate HTML report
php artisan route:audit --export html --filename security-audit.html

# JSON export for automation
php artisan route:audit --export json --filename audit-results.json
```

**Example Output**:
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        admin.users.destroy                  -25
  ✖        api.orders.index                     50
  ✓        users.index                          75
  -------- ------------------------------------ -------- 

[ERROR] 2/3 routes scored below the benchmark
```

---

### route:audit-report

Generates comprehensive multi-section HTML report combining all audit types.

**Purpose**: Create complete security assessment dashboard for stakeholder review.

**Signature**: `route:audit-report`

**Generated Reports**:
- **Main Report**: Overall security assessment
- **Authentication**: Auth middleware analysis
- **Test Coverage**: PHPUnit coverage analysis
- **Detailed Coverage**: Role-specific test coverage
- **Scoped Bindings**: Route model binding security

**Usage**:
```bash
php artisan route:audit-report
```

**Output**: Creates `storage/exports/audit-routes/index.html` with links to individual reports.

**File Structure**:
```
storage/exports/audit-routes/
├── index.html           # Main dashboard
├── report.html          # Comprehensive audit
├── auth.html            # Authentication analysis
├── php-unit.html        # Test coverage
├── php-unit-roles.html  # Detailed coverage
└── scoped-bindings.html # Model binding security
```

---

### route:audit-auth

Authentication middleware analysis focused on identifying unprotected routes.

**Purpose**: Verify routes have appropriate authentication requirements (web auth or API tokens).

**Signature**: `route:audit-auth {--export=} {--filename=} {-v|vv|vvv}`

**Analysis**:
- **Web routes**: Checks for `auth` middleware
- **API routes**: Validates `auth:sanctum` middleware
- **Mixed routes**: Identifies inconsistent auth patterns

**Usage Examples**:
```bash
# Console analysis
php artisan route:audit-auth -vv

# HTML report generation
php artisan route:audit-auth --export html --filename auth-analysis.html
```

**Key Metrics**:
- **Total routes**: Count of analyzed routes
- **Guest rate**: Percentage of unauthenticated routes
- **Authenticated rate**: Percentage with proper auth

---

### route:audit-test-coverage

PHPUnit test coverage analysis for route protection against regressions.

**Purpose**: Identify untested routes that lack automated test coverage.

**Signature**: `route:audit-test-coverage {--benchmark=1} {--export=} {--filename=} {-v|vv|vvv}`

**Options**:
- `--benchmark=N`: Minimum acceptable test count per route

**Analysis Method**:
- Parses PHPUnit test files using AST analysis
- Identifies HTTP test methods (`get`, `post`, `getJson`, etc.)
- Matches test calls to route definitions
- Counts test coverage per route

**Usage Examples**:
```bash
# Basic coverage check
php artisan route:audit-test-coverage -vv

# Require at least 2 tests per route
php artisan route:audit-test-coverage --benchmark 2

# Generate detailed coverage report
php artisan route:audit-test-coverage --export html --filename coverage-report.html
```

**Metrics Provided**:
- **Total routes**: Number of defined routes
- **Uncovered rate**: Percentage without tests
- **Covered rate**: Percentage with adequate tests
- **Average coverage**: Mean tests per route

## Global Command Options

### Verbosity Levels

**Default**: Basic success/failure summary
```bash
php artisan route:audit
# Output: "32 routes passed, 13 failed"
```

**Verbose (-v)**: Add route names and scores
```bash
php artisan route:audit -v
# Shows individual route results
```

**Very Verbose (-vv)**: Include detailed analysis
```bash
php artisan route:audit -vv
# Shows auditor breakdown, scores, reasons
```

**Debug (-vvv)**: Full diagnostic information
```bash
php artisan route:audit -vvv
# Internal processing details, timing, memory usage
```

### Export Formats

#### HTML Export
Generates styled, shareable reports with visual indicators and filtering.

**Features**:
- Color-coded security status
- Interactive filtering by score/status
- Detailed auditor breakdowns
- Executive summary metrics

**Usage**:
```bash
php artisan route:audit --export html --filename security-report.html
```

#### JSON Export
Machine-readable format for automation and integration.

**Structure**:
```json
{
  "summary": {
    "total_routes": 45,
    "passed": 32,
    "failed": 13,
    "average_score": 67.2
  },
  "routes": [
    {
      "name": "users.index",
      "score": 100,
      "auditors": {
        "AuthAuditor": 25,
        "PolicyAuditor": 25,
        "TestAuditor": 50
      }
    }
  ]
}
```

**Usage**:
```bash
php artisan route:audit --export json --filename results.json
```

### Benchmark Thresholds

Set minimum acceptable security scores to define compliance standards.

**Common Benchmarks**:
- **0**: Flag only negative scores (minimal standards)
- **25**: Basic security compliance
- **50**: Moderate security requirements
- **75**: High security standards
- **100**: Maximum security compliance

**Usage**:
```bash
# High security environment
php artisan route:audit --benchmark 75

# Development environment
php artisan route:audit --benchmark 25
```

## Command Integration Patterns

### CI/CD Pipeline Integration
```bash
#!/bin/bash
# security-audit.sh

php artisan route:audit --benchmark 50 --export json --filename audit.json

# Check exit code
if [ $? -ne 0 ]; then
    echo "❌ Route security audit failed"
    exit 1
fi

echo "✅ Route security audit passed"
```

### Automated Reporting
```bash
#!/bin/bash
# daily-security-report.sh

DATE=$(date +%Y-%m-%d)
php artisan route:audit-report
mv storage/exports/audit-routes storage/reports/audit-${DATE}

# Send report notification
echo "Security report generated: audit-${DATE}" | mail -s "Daily Security Audit" security@company.com
```

### Development Workflow
```bash
# Pre-commit hook
php artisan route:audit --benchmark 25 --export json --filename /tmp/audit.json
FAILED=$(jq '.summary.failed' /tmp/audit.json)

if [ "$FAILED" -gt 0 ]; then
    echo "⚠️  $FAILED routes failed security audit"
    echo "Run: php artisan route:audit -vv for details"
fi
```

## Error Handling

**Common Exit Codes**:
- `0`: All routes pass benchmark
- `1`: Some routes below benchmark
- `2`: Configuration errors
- `3`: File system errors

**Troubleshooting**:
```bash
# Test configuration
php artisan route:audit --benchmark 0 -v

# Verify output permissions
php artisan route:audit --export html --filename test.html

# Check route discovery
php artisan route:list
```

## Next Steps

- **[Auditors API](auditors.md)**: Understand individual auditor classes
- **[Assertions API](assertions.md)**: PHPUnit testing integration
- **[Basic Usage Guide](../../guides/basic-usage.md)**: Practical command examples
- **[CI Integration Guide](../../guides/ci-integration.md)**: Automation setup