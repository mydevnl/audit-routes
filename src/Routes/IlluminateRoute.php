<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Routes;

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

    /** @return null | string */
    public function getName(): ?string
    {
        return $this->route->getName();
    }

    /** @return string */
    public function getUri(): string
    {
        return $this->route->uri();
    }

    /** @return string */
    public function getIdentifier(): string
    {
        return $this->getName() ?? $this->getUri();
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

    /** @return bool */
    public function hasScopedBindings(): bool
    {
        return $this->route->enforcesScopedBindings();
    }
}
