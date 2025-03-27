<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use JsonSerializable;
use MyDev\AuditRoutes\Entities\AuditedRoute;

interface AggregatorInterface extends JsonSerializable
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
     * @return static
     */
    public function setName(?string $name): static;

    /** @return null | float | array<int | string, AggregatorInterface> */
    public function getResult(): null | float | array;
}