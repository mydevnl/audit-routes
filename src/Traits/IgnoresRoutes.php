<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

use Illuminate\Support\Str;
use MyDev\AuditRoutes\Contracts\RouteInterface;

trait IgnoresRoutes
{
    /** @var array<int, string> $ignoredRoutes */
    protected array $ignoredRoutes = [];

    /** @var array<int, string> $defaultIgnoredRoutes */
    protected array $defaultIgnoredRoutes = [];

    /** @param array<int, string> $routes */
    public function ignoreRoutes(array $routes): self
    {
        $this->ignoredRoutes = $routes;

        return $this;
    }

    /**
     * @param RouteInterface $route
     * @return bool
     */
    protected function validateRoute(RouteInterface $route): bool
    {
        if (empty($route->getIdentifier())) {
            return false;
        }

        $allIgnoredRoutes = array_merge($this->defaultIgnoredRoutes, $this->ignoredRoutes);

        foreach ($allIgnoredRoutes as $ignoredRoute) {
            if (Str::of($route->getIdentifier())->is($ignoredRoute)) {
                return false;
            }
        }

        return true;
    }
}
