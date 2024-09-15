<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Repositories\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class MiddlewareAuditor implements AuditorInterface
{
    use Auditable;

    protected array $middlewares = [];

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        $implementedMiddlewares = $route->getMiddlewares();
        $implementationCount = 0;

        foreach ($this->middlewares as $middleware) {
            if (in_array($middleware, $implementedMiddlewares)) {
                $implementationCount++;
            }
        }

        return $this->getScore($implementationCount);
    }

    /**
     * @param array<int | string, mixed> $arguments
     * @return self
     */
    public function setArguments(array $arguments): self
    {
        $this->middlewares = $arguments;

        return $this;
    }
}
