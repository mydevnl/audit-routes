<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class Group implements AggregatorInterface
{
    use Aggregateable;

    /** @var array<int | string, AggregatorInterface> $result */
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'aggregator' => $this->getAggregator(),
            'name'       => $this->getName(),
            'result'     => array_map(
                fn (AggregatorInterface $childAggregator): array => $childAggregator->toArray(),
                $this->result,
            ),
        ];
    }
}
