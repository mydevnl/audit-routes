<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Testing\Concerns;

use Closure;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Routes\RouteInterface;

trait AssertsRouteTested
{
    use AssertsAuditRouteStatus;

    /**
     * Assert that each given route is covered in tests.
     *
     * @param iterable                                                             $routes
     * @param int                                                                  $times
     * @param array<int, string>                                                   $ignoredRoutes
     * @param null | string | Closure(AuditedRouteCollection): string              $message
     * @param null | Closure(RouteInterface): bool $when
     * @return static
     */
    protected function assertRoutesAreTested(
        iterable $routes,
        int $times = 1,
        array $ignoredRoutes = [],
        null | string | Closure $message = null,
        ?Closure $when = null,
    ): static {
        $auditor = TestAuditor::make()->setPenalty(-1)->when($when ?? fn (): bool => true);

        $message ??= function (AuditedRouteCollection $failedRoutes): string {
            $lines = ['The following routes appear to be missing test coverage:', ...$failedRoutes->get()];

            return implode("\n\t", $lines);
        };

        return $this->assertAuditRoutesOk($routes, [$auditor], $message, $ignoredRoutes, $times);
    }

    /**
     * Assert a specific route is covered in tests.
     * 
     * @param mixed         $route
     * @param int           $times
     * @param null | string $message
     * @return static
     */
    protected function assertRouteIsTested(mixed $route, int $times = 1, ?string $message = null): static
    {
        $message ??= 'The route appears to be missing test coverage';

        return $this->assertRoutesAreTested([$route], $times, message: $message);
    }
}
