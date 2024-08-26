<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Repositories;

class StringableRoute implements RouteInterface
{
    public function __construct(protected string $route)
    {
    }

    public static function for(string $route): self
    {
        return new self($route);
    }

    public function getName(): string
    {
        return $this->route;
    }

    /** @return array<int, string | callable> */
    public function getMiddlewares(): array
    {
        return [];
    }

    public function getClass(): string
    {
        return gettype($this->route);
    }
}
