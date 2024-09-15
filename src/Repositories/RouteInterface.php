<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Repositories;

interface RouteInterface
{
    /** @return string */
    public function getName(): string;

    /** @return array<int, string | callable> */
    public function getMiddlewares(): array;

    /** @return string */
    public function getClass(): string;
}
