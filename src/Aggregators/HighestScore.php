<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class HighestScore implements AggregatorInterface
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
        $this->visitedCount++;

        if ($auditedRoute->getScore() < $this->result && $this->visitedCount > 1) {
            return;
        }

        $this->result = $auditedRoute->getScore();
    }

    /** @return string */
    public function getName(): string
    {
        return 'Highest score';
    }
}
