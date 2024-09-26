<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

/** @mixin \MyDev\AuditRoutes\Auditors\AggregatorInterface */
trait Aggregateable
{
    /** @return float */
    public function getResult(): float
    {
        if (!property_exists($this, 'result')) {
            return 0;
        }

        return $this->result;
    }

    /** @return array<string, string | float> */
    public function toArray(): array
    {
        return [
            'name'   => $this->getName(),
            'result' => $this->getResult(),
        ];
    }

    /** @return array<string, string | float> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
