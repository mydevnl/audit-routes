<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
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
