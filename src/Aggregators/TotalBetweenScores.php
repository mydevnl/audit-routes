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
     * @param int|null $from
     * @param int|null $till
     */
    public function __construct(?string $name, protected ?int $from, protected ?int $till)
    {
        $this->setName($name);
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        if (!is_null($this->from) && $auditedRoute->getScore() < $this->from) {
            return;
        }

        if (!is_null($this->till) && $auditedRoute->getScore() > $this->till) {
            return;
        }

        $this->result++;
    }
}
