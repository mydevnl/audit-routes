<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Repositories;

interface RouteInterface
{
    public function getName(): string;

    /** @return array<int, string | callable> */
    public function getMiddlewares(): array;

    public function getClass(): string;
}
