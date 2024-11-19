<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

trait TracksRouteOccurrences
{
    /** @var array<string, int> $routeOccurrences */
    protected array $routeOccurrences = [];

    /**
     * @param string $route
     * @return void
     */
    public function markRouteOccurrence(string $route): void
    {
        if (!isset($this->routeOccurrences[$route])) {
            $this->routeOccurrences[$route] = 0;
        }
        $this->routeOccurrences[$route]++;
    }

    /**
     * @param string $route
     * @return int
     */
    public function getRouteOccurrence(string $route): int
    {
        return $this->routeOccurrences[$route] ?? 0;
    }
}
