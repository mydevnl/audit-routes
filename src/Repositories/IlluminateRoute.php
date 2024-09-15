<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Repositories;

use Illuminate\Routing\Route;

class IlluminateRoute implements RouteInterface
{
    /**
     * @param Route $route
     * @return void
     */
    public function __construct(protected Route $route)
    {
    }

    /**
     * @param Route $route
     * @return self
     */
    public static function for(Route $route): self
    {
        return new self($route);
    }

    /** @return string */
    public function getName(): string
    {
        return (string) $this->route->getName();
    }

    /** @return array<int, string | callable> */
    public function getMiddlewares(): array
    {
        return $this->route->gatherMiddleware();
    }

    /** @return string */
    public function getClass(): string
    {
        return $this->route::class;
    }
}
