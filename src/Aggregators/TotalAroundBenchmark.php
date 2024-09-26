<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class TotalAroundBenchmark implements AggregatorInterface
{
    use Aggregateable;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param float $percentageBelow
     * @param float $percentageAbove
     * @return void
     */
    public function __construct(protected float $percentageBelow, protected float $percentageAbove)
    {
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        $from = $auditedRoute->getBenchmark() * (1 - ($this->percentageBelow / 100));

        if ($auditedRoute->getScore() < $from) {
            return;
        }

        $till = $auditedRoute->getBenchmark() * (1 + ($this->percentageAbove / 100));

        if ($auditedRoute->getScore() > $till) {
            return;
        }

        $this->result++;
    }

    /** @return string */
    public function getName(): string
    {
        return "Total between -{$this->percentageBelow}% and +{$this->percentageAbove}% from benchmark";
    }
}
