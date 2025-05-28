<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

interface RouteOccurrenceTrackerInterface
{
    /**
     * @param string ...$routes
     * @return void
     */
    public function markRouteOccurrence(string ...$routes): void;

    /**
     * @param array $routeOccurrences
     * @return void
     */
    public function markRouteOccurrences(array $routeOccurrences): void;

    /**
     * @param string $route
     * @return int
     */
    public function getRouteOccurrence(string $route): int;

    /** @return array<string, int> */
    public function getRouteOccurrences(): array;
}
