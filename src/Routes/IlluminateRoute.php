<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Routes;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\MiddlewareNameResolver;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

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

    /** @return array<int, Middleware> */
    public function getMiddlewares(): array
    {
        return array_map(
            fn (string $middleware): Middleware => $this->resolveMiddleware($middleware),
            $this->route->gatherMiddleware(),
        );
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

    /**
     * @param string $middleware
     * @return Middleware
     */
    protected function resolveMiddleware(string $middleware): Middleware
    {
        [$alias] = explode(':', $middleware, 2);

        try {
            /** @var Kernel $kernel */
            $kernel = App::make(Kernel::class);
            $reflection = new ReflectionClass($kernel);

            $aliasProperty = match (true) {
                $reflection->hasProperty('middlewareAliases') => $reflection->getProperty('middlewareAliases'),
                $reflection->hasProperty('routeMiddleware')   => $reflection->getProperty('routeMiddleware'),
                default                                       => throw new RuntimeException(
                    'No middleware aliases found on the Kernel.',
                ),
            };

            $aliasProperty->setAccessible(true);
            /** @var array<string, string> $aliases */
            $aliases = $aliasProperty->getValue($kernel);

            $resolvedMiddleware = MiddlewareNameResolver::resolve($middleware, $aliases, []);

            return Middleware::from($resolvedMiddleware, $alias);
        } catch (ReflectionException) {
            return Middleware::from($middleware, $alias);
        }
    }
}
