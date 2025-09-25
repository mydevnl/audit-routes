# Troubleshooting Guide

Common issues and solutions when working with Audit Routes. This guide covers configuration problems, command failures, performance issues, and unexpected results.

## Installation and Setup Issues

### Package Not Auto-Registered

**Problem**: Commands not available after installation
```bash
$ php artisan route:audit
Command "route:audit" is not defined.
```

**Solutions**:
1. **Check Laravel version**: Ensure Laravel 7.0+ for auto-discovery
2. **Clear cache**: `php artisan config:clear && php artisan cache:clear`
3. **Manual registration**: Add to `config/app.php` providers array:
   ```php
   'providers' => [
       MyDev\AuditRoutes\AuditRoutesServiceProvider::class,
   ],
   ```
4. **Composer autoload**: `composer dump-autoload`

### Configuration File Issues

**Problem**: Configuration not found or invalid
```
Configuration file audit-routes.php not found
```

**Solutions**:
1. **Publish config**: `php artisan vendor:publish --tag=audit-routes-config`
2. **Check file permissions**: Ensure `config/audit-routes.php` is readable
3. **Validate syntax**: Check for PHP syntax errors in config file
4. **Reset to defaults**: Delete and republish configuration

## Command Execution Problems

### No Routes Found

**Problem**: Audits show zero routes
```
Total Routes: 0 | Average Score: N/A
```

**Diagnostic Steps**:
1. **Verify route registration**:
   ```bash
   php artisan route:list
   ```
2. **Clear route cache**:
   ```bash
   php artisan route:clear
   php artisan config:clear
   ```
3. **Check ignored routes**: Review `ignored-routes` in config
4. **Verify route naming**: Ensure routes have proper names

**Common Causes**:
- Routes defined but not named
- All routes match ignore patterns
- Route caching issues
- Missing route service provider registration

### Permission Denied Errors

**Problem**: Cannot write export files
```
Permission denied: storage/exports/audit-routes/report.html
```

**Solutions**:
1. **Fix directory permissions**:
   ```bash
   chmod -R 775 storage/
   chown -R www-data:www-data storage/
   ```
2. **Create directory manually**:
   ```bash
   mkdir -p storage/exports/audit-routes
   chmod 775 storage/exports/audit-routes
   ```
3. **Check Laravel permissions**: Verify storage directory is writable
4. **SELinux issues** (CentOS/RHEL):
   ```bash
   setsebool -P httpd_can_network_connect 1
   ```

### Memory or Timeout Issues

**Problem**: Commands fail on large applications
```
Fatal error: Allowed memory size exhausted
```

**Solutions**:
1. **Increase memory limit**:
   ```bash
   php -d memory_limit=512M artisan route:audit
   ```
2. **Use specific auditors**: Avoid running all auditors simultaneously
3. **Audit route subsets**: Use pattern matching to audit sections
4. **Optimize test directory**: Ensure test files are well-organized

**Performance Optimization**:
```bash
# Audit specific route patterns
php artisan route:audit-auth  # Authentication only
php artisan route:audit-test-coverage  # Tests only

# Focus on problem areas
php artisan route:audit -vv | grep "Score: -"
```

## Test Coverage Analysis Issues

### Tests Not Found

**Problem**: All routes show zero test coverage
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        users.index                          -50
```

**Diagnostic Steps**:
1. **Verify test directory**:
   ```php
   // config/audit-routes.php
   'tests' => [
       'directory' => 'tests',  // Correct path?
   ]
   ```
2. **Check test class inheritance**:
   ```php
   'tests' => [
       'implementation' => \Tests\TestCase::class,  // Correct base class?
   ]
   ```
3. **Validate test methods**: Ensure methods use configured `acting-methods`

**Common Issues**:
- Wrong test directory path
- Incorrect base test class
- Tests don't use HTTP test methods (`get`, `post`, etc.)
- Route names don't match test route calls

### False Negatives in Test Detection

**Problem**: Tests exist but not detected
```php
// This test exists but isn't counted
public function test_user_can_view_profile()
{
    $this->get('/profile')->assertStatus(200);  // Uses URL, not route name
}
```

**Solutions**:
1. **Use route names in tests**:
   ```php
   $this->get(route('profile.show'))->assertStatus(200);
   ```
2. **Update acting-methods config**:
   ```php
   'acting-methods' => [
       'get', 'post', 'getJson', 'postJson',
       'call', 'json',  // Add missing methods
   ]
   ```
3. **Check method naming**: Ensure test methods are properly named

## Auditor Configuration Problems

### Unexpected Scoring Results

**Problem**: Routes have unexpected scores
```
Expected positive score but got: -25
```

**Debugging Steps**:
1. **Use verbose output**: `php artisan route:audit -vv`
2. **Check auditor weights**:
   ```php
   // Positive weights reward compliance
   PolicyAuditor::make()->setWeight(25)

   // Negative weights penalize non-compliance
   PolicyAuditor::make()->setPenalty(-25)
   ```
3. **Verify auditor logic**: Test with single auditor
4. **Review route middleware**: Check actual vs expected middleware

**Common Scoring Issues**:
- Mixing weights and penalties incorrectly
- Auditor detecting different middleware than expected
- Route middleware not properly configured
- Scoped binding detection errors

### Middleware Detection Problems

**Problem**: MiddlewareAuditor not detecting existing middleware
```
 -------- ------------------------------------ -------- 
  Status   Route                                Score   
 -------- ------------------------------------ -------- 
  ✖        orders.index                         -50 # But route has 'auth' middleware
```

**Solutions**:
1. **Check middleware names**:
   ```php
   Route::get('/orders', [OrderController::class, 'index'])
       ->middleware('auth');  // Use exact name
   ```
2. **Verify middleware registration**:
   ```bash
   php artisan route:list --columns=uri,name,middleware
   ```
3. **Update auditor configuration**:
   ```php
   MiddlewareAuditor::make(['auth', 'auth:sanctum'])  // Include variations
   ```

## Policy and Authorization Issues

### Policy Detection Failures

**Problem**: PolicyAuditor not detecting existing policies
```
Route has 'can:view,post' but PolicyAuditor shows negative score
```

**Debugging**:
1. **Check middleware syntax**:
   ```php
   // Correct policy middleware
   Route::get('/posts/{post}', [PostController::class, 'show'])
       ->middleware('can:view,post');
   ```
2. **Verify policy registration**: Ensure policies are properly registered
3. **Use verbose output**: Check what PolicyAuditor detects

### Scoped Binding Detection

**Problem**: ScopedBindingAuditor false positives/negatives
```
Route with scopeBindings() still fails audit
```

**Solutions**:
1. **Verify scoped binding syntax**:
   ```php
   Route::get('/users/{user}/posts/{post}', [PostController::class, 'show'])
       ->scopeBindings();  // Correct placement
   ```
2. **Check route parameters**: Ensure proper parameter naming
3. **Review model relationships**: Verify parent-child relationships exist

## Performance Issues

### Slow Audit Execution

**Problem**: Audits take too long to complete

**Optimization Strategies**:
1. **Limit test file parsing**:
   ```php
   // config/audit-routes.php
   'tests' => [
       'directory' => 'tests/Feature',  // More specific directory
   ]
   ```
2. **Use focused auditors**:
   ```bash
   # Instead of comprehensive audit
   php artisan route:audit-auth  # Faster, focused analysis
   ```
3. **Filter routes**:
   ```bash
   # Audit specific route patterns
   php artisan route:audit --pattern="admin.*"
   ```

### Memory Usage Optimization

**Problem**: High memory usage during analysis

**Solutions**:
1. **Process routes in batches**: Implement custom command for large applications
2. **Exclude large test files**: Focus on relevant test directories
3. **Optimize ignored routes**: Exclude development/debug routes
4. **Use streaming for reports**: Avoid loading all results in memory

## Integration Problems

### CI/CD Pipeline Failures

**Problem**: Audits fail in automated environments
```bash
# CI environment error
php artisan route:audit --benchmark 50
# Exit code 1 (failure)
```

**Solutions**:
1. **Environment-specific benchmarks**:
   ```bash
   # Different standards for different environments
   BENCHMARK=${CI_BENCHMARK:-25} php artisan route:audit --benchmark $BENCHMARK
   ```
2. **Handle exit codes properly**:
   ```bash
   php artisan route:audit || echo "Security audit warnings detected"
   ```
3. **Use JSON output for parsing**:
   ```bash
   php artisan route:audit --export json --filename ci-results.json
   FAILED=$(jq '.summary.failed' ci-results.json)
   ```

### PHPUnit Integration Issues

**Problem**: Assertions failing unexpectedly
```php
// Assertion fails but manual audit passes
$this->assertRoutesAreTested(['users.index']);
```

**Debugging**:
1. **Check test environment**: Ensure same routes loaded in tests
2. **Verify route names**: Use `php artisan route:list` to confirm names
3. **Test isolation**: Ensure tests don't affect route registration
4. **Database state**: Some tests might require specific data

## Configuration Best Practices

### Development vs Production

**Development configuration**:
```php
return [
    'ignored-routes' => [
        'telescope*', 'debugbar.*', 'ignition.*',
    ],
    'benchmark' => 25,  // Relaxed standards
];
```

**Production configuration**:
```php
return [
    'ignored-routes' => [],  // Minimal exclusions
    'benchmark' => 75,  // Strict standards
];
```

### Large Application Optimization

**Strategies for applications with 500+ routes**:
1. **Incremental adoption**: Start with critical routes
2. **Team-specific audits**: Audit by feature/team boundaries
3. **Automated monitoring**: Regular security checks
4. **Custom commands**: Build application-specific audit commands

## Getting Help

### Debug Information Collection

When reporting issues, include:
1. **Laravel version**: `php artisan --version`
2. **Package version**: Check `composer.lock`
3. **Configuration**: Sanitized `config/audit-routes.php`
4. **Route sample**: `php artisan route:list | head -10`
5. **Verbose output**: `php artisan route:audit -vv`

### Common Command Combinations

```bash
# Full diagnostic
php artisan route:clear
php artisan config:clear
php artisan route:list | wc -l  # Count routes
php artisan route:audit --benchmark 0 -vv

# Permission fix
sudo chown -R $USER:www-data storage/
chmod -R 775 storage/

# Memory optimization
php -d memory_limit=1G artisan route:audit-report
```

### Support Resources

- **GitHub Issues**: Report bugs and feature requests
- **Discussion Forum**: Community questions and solutions
- **Documentation**: Reference guides and examples

## Next Steps

- **[Configuration Guide](../getting-started/configuration.md)**: Optimize settings for your environment
- **[Testing Guide](testing.md)**: Set up PHPUnit assertions and test suite integration
- **[Advanced Usage](advanced-usage.md)**: Complex audit patterns and custom solutions
- **[Basic Usage Guide](basic-usage.md)**: Review fundamental patterns
- **[Custom Auditors](custom-auditors.md)**: Build application-specific solutions
- **[CI Integration](ci-integration.md)**: Automate audits in your deployment pipeline