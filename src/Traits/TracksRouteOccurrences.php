<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

trait TracksRouteOccurrences
{
    /** @var array<string, int> $routeOccurrences */
    private array $routeOccurrences = [];

    public function markRouteOccurrence(string $route): void
    {
        if (!isset($this->routeOccurrences[$route])) {
            $this->routeOccurrences[$route] = 0;
        }
        $this->routeOccurrences[$route]++;
    }

    public function getRouteOccurrence(string $route): int
    {
        return $this->routeOccurrences[$route] ?? 0;
    }
}
