<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use Closure;
use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class ConditionedCount implements AggregatorInterface
{
    use Aggregateable;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param null | string $name
     * @param null | Closure(AuditedRoute): bool $condition
     * @return void
     */
    public function __construct(?string $name = null, protected ?Closure $condition = null)
    {
        $this->setName($name);
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        if (is_callable($this->condition) && !($this->condition)($auditedRoute)) {
            return;
        }

        $this->result++;
    }
}
