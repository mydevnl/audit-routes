<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

use MyDev\AuditRoutes\Repositories\RouteInterface;

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
        if (empty($route->getName())) {
            return false;
        }

        $allIgnoredRoutes = array_merge($this->defaultIgnoredRoutes, $this->ignoredRoutes);

        foreach ($allIgnoredRoutes as $ignoredRoute) {
            if ($route->getName() === $ignoredRoute) {
                return false;
            }

            [$ignoredRouteGroup, $suffix] = str_split($ignoredRoute, strlen($ignoredRoute) -1);

            if ($suffix === '*' && str_starts_with($route->getName(), $ignoredRouteGroup)) {
                return false;
            }

            [$prefix, $ignoredRouteGroup] = str_split($ignoredRoute, 1);

            if ($prefix === '*' && str_ends_with($route->getName(), $ignoredRouteGroup)) {
                return false;
            }
        }

        return true;
    }
}
