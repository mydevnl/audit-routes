<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Routes;

use Exception;
use Illuminate\Routing\Route as IlluminateRoutingRoute;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Symfony\Component\Routing\Route as SymfonyRoutingRoute;

class RouteFactory
{
    /**
     * @param iterable $routes
     * @throws InvalidArgumentException
     * @return array<int, RouteInterface>
     */
    public static function collection(iterable $routes): array
    {
        $mappedRoutes = [];
        foreach (self::resolveToAllRoutes($routes) as $key => $route) {
            $mappedRoutes[] = self::build($route, $key);
        }

        return $mappedRoutes;
    }

    /**
     * @param mixed $route
     * @param null | string | int $name
     * @throws InvalidArgumentException
     * @return RouteInterface
     */
    public static function build(mixed $route, null | string | int $name = null): RouteInterface
    {
        return match(true) {
            is_string($route)                        => self::buildStringableRoute($route),
            $route instanceof IlluminateRoutingRoute => new IlluminateRoute($route),
            $route instanceof SymfonyRoutingRoute    => new SymfonyRoute($name, $route),
            $route instanceof RouteInterface         => $route,
            default                                  => throw new InvalidArgumentException('Unsupported route'),
        };
    }

    /**
     * @param string $route
     * @return RouteInterface
     */
    protected static function buildStringableRoute(string $route): RouteInterface
    {
        try {
            $router = App::make(Router::class);
            $resolvedRoute = $router->getRoutes()->getByName($route);

            if ($resolvedRoute) {
                return new IlluminateRoute($resolvedRoute);
            }
        } catch (Exception $error) {
            unset($error);
        }

        return new StringableRoute($route);
    }

    /**
     * @param iterable $routes
     * @return iterable
     */
    protected static function resolveToAllRoutes(iterable $routes): iterable
    {
        if ($routes === ['*']) {
            $router = App::make(Router::class);

            return $router->getRoutes()->getRoutes();
        }

        return $routes;
    }
}
