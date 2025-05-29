<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Routes;

use MyDev\AuditRoutes\Contracts\RouteInterface;

class StringableRoute implements RouteInterface
{
    /**
     * @param string $route
     * @return void
     */
    public function __construct(protected string $route)
    {
    }

    /**
     * @param string $route
     * @return self
     */
    public static function for(string $route): self
    {
        return new self($route);
    }

    /** @return string */
    public function getName(): string
    {
        return $this->route;
    }

    /** @return string */
    public function getUri(): string
    {
        return $this->route;
    }

    /** @return string */
    public function getIdentifier(): string
    {
        return $this->route;
    }

    /** @return array<int, string | callable> */
    public function getMiddlewares(): array
    {
        return [];
    }

    /** @return string */
    public function getClass(): string
    {
        return gettype($this->route);
    }

    /** @return null | bool */
    public function hasScopedBindings(): ?bool
    {
        return null;
    }
}
