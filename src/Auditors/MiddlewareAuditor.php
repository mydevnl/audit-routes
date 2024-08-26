<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Repositories\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class MiddlewareAuditor implements AuditorInterface
{
    use Auditable;

    protected array $middlewares = [];

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

    public function setArguments(array $arguments): self
    {
        $this->middlewares = $arguments;

        return $this;
    }
}
