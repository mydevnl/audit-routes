<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Routes;

use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;

class StringableRoute implements RouteInterface
{
    /** @var bool|null $scopedBindings */
    protected ?bool $scopedBindings = null;

    /** @var array<int, Middleware> $middelwares */
    protected array $middelwares = [];

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

    /** @return array<int, Middleware> */
    public function getMiddlewares(): array
    {
        return $this->middelwares;
    }

    /**
     * @param string $middleware
     * @return bool
     */
    public function hasMiddleware(string $middleware): bool
    {
        foreach ($this->getMiddlewares() as $implementedMiddleware) {
            if ($implementedMiddleware->is($middleware)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, Middleware> $middelwares
     * @return self
     */
    public function setMiddlewares(array $middelwares): self
    {
        $this->middelwares = $middelwares;

        return $this;
    }

    /** @return null | bool */
    public function hasScopedBindings(): ?bool
    {
        return $this->scopedBindings;
    }

    /**
     * @param null | bool $scopedBindings
     * @return self
     */
    public function setScopedBindings(?bool $scopedBindings): self
    {
        $this->scopedBindings = $scopedBindings;

        return $this;
    }

    /** @return string */
    public function getClass(): string
    {
        return gettype($this->route);
    }
}
