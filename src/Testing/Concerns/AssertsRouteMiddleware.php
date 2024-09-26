<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Testing\Concerns;

use Closure;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;

trait AssertsRouteMiddleware
{
    use AssertsAuditRouteStatus;

    /**
     * Assert that each given route is has the expected middleware implemented.
     *
     * @param iterable                                                             $routes
     * @param array<int, string>                                                   $middleware
     * @param array<int, string>                                                   $ignoredRoutes
     * @param null | string | Closure(AuditedRouteCollection): string              $message
     * @param null | Closure(\MyDev\AuditRoutes\Routes\RouteInterface): bool $when
     * @return self
     */
    protected function assertRoutesHaveMiddleware(
        iterable $routes,
        array $middleware,
        array $ignoredRoutes = [],
        null | string | Closure $message = null,
        ?Closure $when = null,
    ): self {
        $auditor = MiddlewareAuditor::make($middleware)->setPenalty(-1)->when($when ?? fn (): bool => true);

        $message ??= function (AuditedRouteCollection $failedRoutes): string {
            $lines = ['The following routes are missing the expected middleware:', ...$failedRoutes->get()];

            return implode("\n\t", $lines);
        };

        return $this->assertAuditRoutesOk($routes, [$auditor], $message, $ignoredRoutes, 0);
    }

    /**
     * Assert a specific route has the expected middleware implemented.
     * 
     * @param mixed               $route
     * @param array<int, string>  $middleware
     * @param null | string: void $message
     * @return self
     */
    protected function assertRouteHasMiddleware(mixed $route, array $middleware, ?string $message = null): self
    {
        $message ??= 'The route is missing the expected middleware';

        return $this->assertRoutesHaveMiddleware([$route], $middleware, message: $message);
    }
}
