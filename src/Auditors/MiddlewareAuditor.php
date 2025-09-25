<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use InvalidArgumentException;
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use MyDev\AuditRoutes\Traits\Auditable;

/**
 * Middleware Auditor.
 *
 * Ensures routes have specific required middleware to enforce security,
 * authentication, or other application-level concerns.
 *
 * Scoring:
 * - Routes with required middleware: +1 per matched middleware (multiplied by weight)
 * - Routes without any required middleware: 0 (or the penalty value if set)
 * - Score can be limited using setLimit() to cap maximum points per route
 *
 * Configuration:
 * - Accepts array of middleware names or Middleware objects as arguments
 * - Supports both string middleware names ('auth') and parameterized middleware ('auth:sanctum', 'can:update,user')
 * - Can be configured with conditional auditing using when() method
 * - Supports route ignoring patterns via ignoreRoutes() method
 *
 * @example
 * MiddlewareAuditor::make(['auth', 'verified'])->setWeight(10)->setPenalty(-25)
 * @example
 * MiddlewareAuditor::make(['auth:sanctum'])->when(fn($route) => $route->hasMiddleware('api'))
 */
class MiddlewareAuditor implements AuditorInterface
{
    use Auditable;

    /** @var array<int | string, string | Middleware> $middlewares */
    protected array $middlewares = [];

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        $implementedMiddlewares = array_filter(
            $route->getMiddlewares(),
            fn (Middleware $implementedMiddleware): bool => $implementedMiddleware->is(...$this->middlewares),
        );

        return $this->getScore(count($implementedMiddlewares));
    }

    /**
     * @param null | array<int | string, mixed> $arguments
     * @return self
     */
    public function setArguments(?array $arguments): self
    {
        $this->middlewares = [];

        foreach ($arguments ?? [] as $argument) {
            if (!is_string($argument) && !$argument instanceof Middleware) {
                throw new InvalidArgumentException('Invalid argument provided');
            }
            $this->middlewares[] = $argument;
        }

        return $this;
    }
}
