<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class TotalBetweenScores implements AggregatorInterface
{
    use Aggregateable;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param int $from
     * @param int $till
     * @return void
     */
    public function __construct(protected int $from, protected int $till)
    {
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        if ($auditedRoute->getScore() < $this->from) {
            return;
        }

        if ($auditedRoute->getScore() > $this->till) {
            return;
        }

        $this->result++;
    }

    /** @return string */
    public function getName(): string
    {
        return "Total between {$this->from} and {$this->till}";
    }
}
