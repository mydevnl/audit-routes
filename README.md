# Audit Routes

This PHP Package provides a streamlined approach to gaining insights into the security and protection of your application's routes. In just a few seconds, you can assess critical aspects such as:

- **Test Coverage:** Comprehensive tests cover all routes to ensure reliability
- **Authentication:** Routes requiring authentication are clearly identified
- **Scoped Bindings:** Nested route models are scoped to maintain data integrity
- **Permissions:** Permission or policy checks enforce access control
- **Middleware:** Essential middleware is applied for security and request handling

Audit Routes is your new best friend for keeping your application rock-solid! Spotting potential flaws is now quicker and easier than ever.


[![Latest Stable Version](https://poser.pugx.org/mydevnl/audit-routes/v/stable)](https://packagist.org/packages/mydevnl/audit-routes)
[![Total Downloads](https://poser.pugx.org/mydevnl/audit-routes/downloads)](https://packagist.org/packages/mydevnl/audit-routes)
[![License](https://poser.pugx.org/mydevnl/audit-routes/license)](https://packagist.org/packages/mydevnl/audit-routes)

## Laravel and more supported frameworks

This package is built for Laravel and is designed to be extendable for use with other PHP frameworks, allowing you to leverage its powerful features across a variety of frameworks.

## Installation

You can install the package via Composer:

```bash
composer require mydevnl/audit-routes:dev-main --dev
```

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag=audit-routes-config
```

## Usage

Once installed, setting up custom commands is a breeze. The package provides flexible options that allow you to tailor your route audits to fit your application's specific needs.

```php
AuditRoutes::for($this->router->getRoutes()->getRoutes())
    ->setBenchmark(1000)
    ->run([
        PolicyAuditor::class => 10,
        PermissionAuditor::class => -10,
        PhpUnitAuditor::make()
            ->setLimit(100)
            ->setPenalty(-100)
            ->setWeight(10),
    ]);
```

## Default commands

To help you get started, default commands have been included to demonstrate how to leverage these options effectively.

Check out the `.docs/examples` directory.

### 📦 Output options

The route auditor supports multiple output formats to suit different use cases:

- **Verbose Console Output** – Detailed audit results printed directly to the terminal.
- **HTML Export** – Generate styled, shareable audit reports.
- **JSON Export** – Easily consume audit data in tools or scripts.
- **Custom Benchmarks** – Define and include application-specific metrics.

You can also extend the system using **data aggregators**, which let you plug in custom logic to enrich reports with detailed, context-aware insights.


```bash
php artisan route:audit -vv --benchmark 500 --export html --filename report.html
```

### 📊 Data Exports & Insights

Audit reports are built and exported as HTML by combining multiple HTML exports, each responsible for analyzing a specific aspect of your application's routes.

A **default report** is included, offering an opinionated configuration that serves as both a sensible starting point and a practical example of how commands can be orchestrated together.

You’re free to modify, extend, or build entirely custom reports tailored to your needs—giving you full control over what gets audited and how results are presented.


```bash
php artisan route:audit-report
```

### Test Coverage

Verify that each route is covered by tests and gain insights into the average number of tests per route.

Supports verbose output, HTML and JSON exports, and customizable benchmarks.

```bash
php artisan route:audit-test-coverage -vv --benchmark 1 --export html --filename test.html
```

### Authentication Middleware

Quickly identify which routes require authentication and which do not.

Supports verbose output, HTML and JSON exports.

```bash
php artisan route:audit-auth -vv --export html --filename auth.html
```

## Testing

The package comes with built-in assertions that you can use within PHPUnit by using the `AssertsAuditRoutes` trait. This allows you to run route security checks and audit compliance as part of your continuous integration pipeline.
Note that Pest support will be added in the near future.

Some examples:

```php
// Assert that all routes, or a specified array of routes, are covered in tests.
$this->assertRoutesAreTested(['*']);

// Assert a specific route to be covered in tests.
$this->assertRouteIsTested('welcome');

// Assert that multiple routes are implemented with the specified middleware, while allowing certain routes to be excluded.
$this->assertRoutesHaveMiddleware(['*'], ['auth'], ignoredRoutes: ['welcome', 'api.*']);

// Assert that a specific route is implemented with the specified middleware.
$this->assertRouteHasMiddleware('api.user.index', ['auth:sanctum']);

// Ensure that all specified routes return an OK status when evaluated with custom auditors.
$this->assertAuditRoutesOk($routes, [PolicyAuditor::make()], $message, benchmark: 1);

// Use negative weight to assert that custom auditors are not applied to given routes.
$this->assertAuditRoutesOk(['*'], [PermissionAuditor::make()->setWeight(-1)], $message);
```

## Contributing

We welcome contributions to this project! If you have ideas for improvements or find bugs, please submit them as issues on GitHub. Contributions should be based on issues that are labeled as "accepted for fix." We highly appreciate and encourage community participation.

For additional help or questions, feel free to reach out via GitHub issues.

## Security Vulnerabilities

If you discover any security vulnerabilities, please report them immediately. All security-related issues will be addressed with the highest priority.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## We're still in development

Please be aware that the latest release is experimental and may be unstable.
The roadmap will be published soon. Follow [mydevnl](https://github.com/mydevnl) to stay updated!

May your routes be flawless! 🎉
