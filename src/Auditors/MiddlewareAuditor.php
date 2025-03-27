<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Routes\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class MiddlewareAuditor implements AuditorInterface
{
    use Auditable;

    /** @var array<int | string, mixed> $middlewares */
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
     * @param null | array<int | string, mixed> $arguments
     * @return self
     */
    public function setArguments(?array $arguments): self
    {
        $this->middlewares = $arguments ?? [];

        return $this;
    }
}
