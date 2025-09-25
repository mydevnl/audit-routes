# Code Examples

This directory contains practical code examples that you can use as starting points for your own implementations.

## Available Examples

### Commands (`commands/`)
Ready-to-use Artisan commands that demonstrate different audit patterns:

- **`AuthenticatedCommand.php`** - Authentication middleware auditing
- **`AuditReportCommand.php`** - Comprehensive reporting command
- **`PhpUnitCoverageCommand.php`** - Test coverage analysis
- **`PhpUnitDetailedCoverageCommand.php`** - Detailed test coverage with metrics
- **`ScopedBindingCommand.php`** - Scoped model binding auditing
- **`AdvancedReportingCommand.php`** - Advanced reporting with aggregators

### Tests (`tests/`)
Example test implementations:

- **`AuditRoutesAreAwesomeTest.php`** - PHPUnit assertions and testing patterns

## Usage

Copy any of these files to your Laravel application and modify them according to your needs:

```bash
# Copy a command example
cp docs/examples/commands/AuthenticatedCommand.php app/Console/Commands/

# Copy a test example
cp docs/examples/tests/AuditRoutesAreAwesomeTest.php tests/Feature/
```

## Documentation Examples

For comprehensive integration examples and patterns, see:
- **[Real-world Examples](../reference/examples/real-world.md)** - Production use cases
- **[Integration Examples](../reference/examples/integrations.md)** - Framework integrations and tooling

## Related Guides

- **[Custom Auditors](../guides/custom-auditors.md)** - Build your own auditors
- **[Testing Guide](../guides/testing.md)** - PHPUnit assertions and testing
- **[CI Integration](../guides/ci-integration.md)** - Automated audits in pipelines