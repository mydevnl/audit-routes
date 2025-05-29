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
     * @param null | float $fractionBelow
     * @param null | float $fractionAbove
     * @return void
     */
    public function __construct(?string $name, protected ?float $fractionAbove, protected ?float $fractionBelow)
    {
        $this->setName($name);
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        if ($this->fractionAbove && $auditedRoute->getScore() < $auditedRoute->getBenchmark() * $this->fractionAbove) {
            return;
        }

        if ($this->fractionBelow && $auditedRoute->getScore() > $auditedRoute->getBenchmark() * $this->fractionBelow) {
            return;
        }

        $this->result++;
    }

    /**
     * @param float $fraction
     * @return string
     */
    protected function fractionToPercentage(float $fraction): string
    {
        return round($fraction * 100, 2) . '%';
    }
}
