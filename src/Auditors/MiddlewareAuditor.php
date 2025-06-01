<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use InvalidArgumentException;
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use MyDev\AuditRoutes\Traits\Auditable;

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
