<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Repositories;

use Illuminate\Routing\Route;

class IlluminateRoute implements RouteInterface
{
    public function __construct(protected Route $route)
    {
    }

    public static function for(Route $route): self
    {
        return new self($route);
    }

    public function getName(): string
    {
        return (string) $this->route->getName();
    }

    /** @return array<int, string | callable> */
    public function getMiddlewares(): array
    {
        return $this->route->gatherMiddleware();
    }

    public function getClass(): string
    {
        return $this->route::class;
    }
}
