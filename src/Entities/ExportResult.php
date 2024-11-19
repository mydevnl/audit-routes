<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use JsonSerializable;

class ExportResult implements JsonSerializable
{
    /**
     * @param array<int, \MyDev\AuditRoutes\Aggregators\AggregatorInterface> $aggregates
     * @param array<int, AuditedRoute> $routes
     * @return void
     */
    public function __construct(public readonly array $aggregates, public readonly array $routes)
    {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'aggregates' => $this->aggregates,
            'routes'     => $this->routes,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
