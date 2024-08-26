<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Testing\Concerns;

use Closure;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;

trait AssertsRouteMiddleware
{
    use AssertsAuditRouteStatus;

    /**
     * Assert that each given route is has the expected middleware implemented.
     *
     * @param iterable                                                             $routes
     * @param array<int, string>                                                   $ignoredRoutes
     * @param null | Closure(\MyDev\AuditRoutes\Repositories\RouteInterface): bool $when
     */
    protected function assertRoutesHaveMiddleware(
        iterable $routes,
        array $middleware,
        array $ignoredRoutes = [],
        ?string $message = null,
        ?Closure $when = null,
    ): self {
        $auditor = MiddlewareAuditor::make($middleware)->setPenalty(-1)->when($when ?? fn (): bool => true);

        $message ??= function (array $failedRoutes): string {
            $lines = ['The following routes are missing the expected middleware:', ...$failedRoutes];

            return implode("\n\t", $lines);
        };

        return $this->assertAuditRoutesOk($routes, [$auditor], $message, $ignoredRoutes, 0);
    }

    /**
     * Assert a specific route has the expected middleware implemented.
     */
    protected function assertRouteHasMiddleware(mixed $route, array $middleware, ?string $message = null): self
    {
        $message ??= 'The route is missing the expected middleware';

        return $this->assertRoutesHaveMiddleware([$route], $middleware, message: $message);
    }
}
