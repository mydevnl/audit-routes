<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use MyDev\AuditRoutes\Entities\AuditedRoute;

interface AggregatorInterface extends JsonSerializable, Arrayable
{
    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void;

    /** @return string */
    public function getName(): string;

    /** @return float */
    public function getResult(): float;
}
