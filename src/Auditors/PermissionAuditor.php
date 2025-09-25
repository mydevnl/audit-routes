<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use MyDev\AuditRoutes\Traits\Auditable;

/**
 * Permission Authorization Auditor.
 *
 * Ensures routes have proper Laravel permission-based authorization through the 'Authorize' or 'can'
 * middleware to enforce role-based access control with simple permissions.
 *
 * Scoring:
 * - Routes with permission middleware: +1 per permission (multiplied by weight)
 * - Routes without permission middleware: 0 (or the penalty value if set)
 * - Only counts permission middleware with exactly one parameter (e.g., 'can:view-users')
 * - Distinguishes permissions from policies by parameter count
 *
 * Configuration:
 * - No arguments required
 * - Automatically detects Laravel's 'can' middleware and 'Illuminate\Auth\Middleware\Authorize'
 * - Filters for single-parameter 'can' middleware (permissions vs multi-parameter policies)
 * - Can be configured with conditional auditing and route ignoring patterns
 *
 * @example
 * PermissionAuditor::make()->setWeight(25)->setPenalty(-30)
 * @example
 * PermissionAuditor::make()->when(fn($route) => str_contains($route->getIdentifier(), 'admin'))
 */
class PermissionAuditor implements AuditorInterface
{
    use Auditable;

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        $middlewares = array_filter(
            $route->getMiddlewares(),
            function (Middleware $middleware): bool {
                if (!$middleware->is('Illuminate\Auth\Middleware\Authorize', 'can')) {
                    return false;
                }

                return count($middleware->getAttributes()) === 1;
            },
        );

        return $this->getScore(count($middlewares));
    }
}
