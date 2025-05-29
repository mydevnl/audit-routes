<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

interface RouteInterface
{
    /** @return null | string */
    public function getName(): ?string;

    /** @return string */
    public function getUri(): string;

    /** @return string */
    public function getIdentifier(): string;

    /** @return array<int, string | callable> */
    public function getMiddlewares(): array;

    /** @return string */
    public function getClass(): string;

    /** @return null | bool */
    public function hasScopedBindings(): ?bool;
}
