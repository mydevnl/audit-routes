<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Repositories;

use Exception;
use Illuminate\Routing\Route as IlluminateRoutingRoute;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Symfony\Component\Routing\Route as SymfonyRoutingRoute;

class RouteFactory
{
    /** @return array<RouteInterface> */
    public static function collection(iterable $routes): array
    {
        $mappedRoutes = [];
        foreach (self::resolveToAllRoutes($routes) as $key => $route) {
            $mappedRoutes[] = self::build($route, $key);
        }

        return $mappedRoutes;
    }

    public static function build(mixed $route, string | int | null $name = null): RouteInterface
    {
        if (is_string($route)) {
            $route = self::resolveStringableRoute($route);
        }

        return match(true) {
            is_string($route)                        => new StringableRoute($route),
            $route instanceof IlluminateRoutingRoute => new IlluminateRoute($route),
            $route instanceof SymfonyRoutingRoute    => new SymfonyRoute($name, $route),
            $route instanceof RouteInterface         => $route,
            default                                  => throw new Exception('Unsupported route'),
        };
    }

    protected static function resolveToAllRoutes(iterable $routes): iterable
    {
        if ($routes === ['*']) {
            $router = App::make(Router::class);

            return $router->getRoutes()->getRoutes();
        }

        return $routes;
    }

    protected static function resolveStringableRoute(string $route): IlluminateRoutingRoute | string
    {
        try {
            $router = App::make(Router::class);
            $resolvedRoute = $router->getRoutes()->getByName($route);

            if ($resolvedRoute) {
                $route = $resolvedRoute;
            }
        } catch (Exception $error) {
            unset($error);
        }

        return $route;
    }
}
