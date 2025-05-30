<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class TotalBetweenScores implements AggregatorInterface
{
    use Aggregateable;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param null | string $name
     * @param int $from
     * @param int $till
     * @return void
     */
    public function __construct(?string $name, protected int $from, protected int $till)
    {
        $this->setName($name);
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
}
