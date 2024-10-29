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

    /** @return void */
    public function after(): void;

    /** @return string */
    public function getAggregator(): string;

    /** @return null | string */
    public function getName(): ?string;

    /**
     * @param null | string $name
     * @return self
     */
    public function setName(?string $name): self;

    /** @return float | array<int, AggregatorInterface> */
    public function getResult(): float | array;
}