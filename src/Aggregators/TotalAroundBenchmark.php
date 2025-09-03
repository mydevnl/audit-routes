<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class TotalAroundBenchmark implements AggregatorInterface
{
    use Aggregateable;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param null | string $name
     * @param null | float $fractionTill
     * @param null | float $fractionFrom
     * @return void
     */
    public function __construct(?string $name, protected ?float $fractionFrom, protected ?float $fractionTill)
    {
        $this->setName($name);
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        if ($this->fractionFrom && $auditedRoute->getScore() < $auditedRoute->getBenchmark() * $this->fractionFrom) {
            return;
        }

        if ($this->fractionTill && $auditedRoute->getScore() > $auditedRoute->getBenchmark() * $this->fractionTill) {
            return;
        }

        $this->result++;
    }
}
