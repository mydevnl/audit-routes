<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use MyDev\AuditRoutes\Traits\Auditable;

/**
 * Policy Authorization Auditor.
 *
 * Ensures routes have proper Laravel policy authorization through the 'Authorize' or 'can'
 * middleware to enforce model-level access control.
 *
 * Scoring:
 * - Routes with policy middleware: +1 per policy (multiplied by weight)
 * - Routes without policy middleware: 0 (or the penalty value if set)
 * - Only counts middleware with multiple parameters (e.g., 'can:view,user')
 *
 * Configuration:
 * - No arguments required
 * - Automatically detects Laravel's 'can' middleware and 'Illuminate\Auth\Middleware\Authorize'
 * - Distinguishes policy middleware from simple 'can' middleware without parameters
 * - Can be configured with conditional auditing and route ignoring patterns
 *
 * @example
 * PolicyAuditor::make()->setWeight(30)->setPenalty(-40)
 * @example
 * PolicyAuditor::make()->when(fn($route) => $route->hasMiddleware('api'))
 */
class PolicyAuditor implements AuditorInterface
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

                return count($middleware->getAttributes()) > 1;
            },
        );

        return $this->getScore(count($middlewares));
    }
}
