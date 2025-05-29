<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class PolicyAuditor implements AuditorInterface
{
    use Auditable;

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        $middlewares = array_filter($route->getMiddlewares(), function (string | callable $middleware): bool {
            if (!is_string($middleware) || !str_contains($middleware, ':')) {
                return false;
            }

            [$can, $permissions] = explode(':', $middleware, 2);

            if ($can !== 'can') {
                return false;
            }

            return count(explode(',', $permissions)) > 1;
        });

        return $this->getScore(count($middlewares));
    }
}
