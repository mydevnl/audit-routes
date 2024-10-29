<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Traits\Aggregateable;

class FailedPercentage implements AggregatorInterface
{
    use Aggregateable;

    /** @var int $visitedCount */
    protected int $visitedCount = 0;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param null | string $name
     * @return void
     */
    public function __construct(?string $name = null)
    {
        $this->setName($name);
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        $failedTotal = $this->visitedCount * ($this->result / 100);

        $this->visitedCount++;

        $addition = $auditedRoute->hasStatus(AuditStatus::Failed) ? 1 : 0;

        $this->result = ($failedTotal + $addition) / $this->visitedCount * 100;
    }
}
