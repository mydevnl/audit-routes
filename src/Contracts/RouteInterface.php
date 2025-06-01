<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

use MyDev\AuditRoutes\Entities\Middleware;

interface RouteInterface
{
    /** @return null | string */
    public function getName(): ?string;

    /** @return string */
    public function getUri(): string;

    /** @return string */
    public function getIdentifier(): string;

    /** @return array<int, Middleware> */
    public function getMiddlewares(): array;

    /**
     * @param string $middleware
     * @return bool
     */
    public function hasMiddleware(string $middleware): bool;

    /** @return string */
    public function getClass(): string;

    /** @return null | bool */
    public function hasScopedBindings(): ?bool;
}
