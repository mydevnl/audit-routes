<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

trait TracksRouteOccurrences
{
    /** @var array<string, int> $routeOccurrences */
    protected array $routeOccurrences = [];

    /**
     * @param string ...$routes
     * @return void
     */
    public function markRouteOccurrence(string ...$routes): void
    {
        foreach ($routes as $route) {
            if (!isset($this->routeOccurrences[$route])) {
                $this->routeOccurrences[$route] = 0;
            }
            $this->routeOccurrences[$route]++;
        }
    }

    /**
     * @param array<string, int> $routeOccurrences
     * @return void
     */
    public function markRouteOccurrences(array $routeOccurrences): void
    {
        foreach ($routeOccurrences as $route => $occurrences) {
            if (!isset($this->routeOccurrences[$route])) {
                $this->routeOccurrences[$route] = 0;
            }
            $this->routeOccurrences[$route] += $occurrences;
        }
    }

    /**
     * @param string $route
     * @return int
     */
    public function getRouteOccurrence(string $route): int
    {
        return $this->routeOccurrences[$route] ?? 0;
    }

    /** @return array<string, int> */
    public function getRouteOccurrences(): array
    {
        return $this->routeOccurrences;
    }
}
