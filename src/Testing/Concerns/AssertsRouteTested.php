<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Testing\Concerns;

use Closure;
use MyDev\AuditRoutes\Auditors\TestAuditor;

trait AssertsRouteTested
{
    use AssertsAuditRouteStatus;

    /**
     * Assert that each given route is covered in tests.
     *
     * @param iterable                                                             $routes
     * @param array<int, string>                                                   $ignoredRoutes
     * @param null | Closure(\MyDev\AuditRoutes\Repositories\RouteInterface): bool $when
     */
    protected function assertRoutesAreTested(
        iterable $routes,
        int $times = 1,
        array $ignoredRoutes = [],
        ?string $message = null,
        ?Closure $when = null,
    ): self {
        $auditor = TestAuditor::make()->setPenalty(-1)->when($when ?? fn (): bool => true);

        $message ??= function (array $failedRoutes): string {
            $lines = ['The following routes appear to be missing test coverage:', ...$failedRoutes];

            return implode("\n\t", $lines);
        };

        return $this->assertAuditRoutesOk($routes, [$auditor], $message, $ignoredRoutes, $times);
    }

    /**
     * Assert a specific route is covered in tests.
     */
    protected function assertRouteIsTested(mixed $route, int $times = 1, ?string $message = null): self
    {
        $message ??= 'The route appears to be missing test coverage';

        return $this->assertRoutesAreTested([$route], $times, message: $message);
    }
}