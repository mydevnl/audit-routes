<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class AverageScore implements AggregatorInterface
{
    use Aggregateable;

    /** @var int $visitedCount */
    protected int $visitedCount = 0;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        $total = ($this->result * $this->visitedCount) + $auditedRoute->getScore();

        $this->visitedCount++;

        $this->result = $total / $this->visitedCount;
    }

    /** @return string */
    public function getName(): string
    {
        return 'Average score';
    }
}