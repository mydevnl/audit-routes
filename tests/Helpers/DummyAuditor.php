<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Helpers;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;
use MyDev\AuditRoutes\Utilities\Cast;

class DummyAuditor implements AuditorInterface
{
    use Auditable;

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        return $this->getScore(Cast::int($route->getName()));
    }
}
