<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class Group implements AggregatorInterface
{
    use Aggregateable;

    /** @var array<int, AggregatorInterface> $result */
    protected array $result = [];

    /**
     * @param null | string $name
     * @param AggregatorInterface ...$aggregators
     * @return void
     */
    public function __construct(?string $name, AggregatorInterface ...$aggregators)
    {
        $this->result = $aggregators;
        $this->setName($name);
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        foreach ($this->result as $aggregator) {
            $aggregator->visit($auditedRoute);
        }
    }

    /** @return void */
    public function after(): void
    {
        foreach ($this->result as $aggregator) {
            $aggregator->after();
        }
    }
}
