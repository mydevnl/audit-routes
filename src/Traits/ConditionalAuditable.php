<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

use Closure;
use MyDev\AuditRoutes\Routes\RouteInterface;

trait ConditionalAuditable
{
    /** @var array<int, Closure(RouteInterface): bool> $conditions */
    protected array $conditions = [];

    /**
     * @param Closure(RouteInterface): bool $condition
     * @return static
     */
    public function when(Closure $condition): static
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * @param RouteInterface $route
     * @return bool
     */
    protected function validateConditions(RouteInterface $route): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition($route)) {
                return false;
            }
        }

        return true;
    }
}
