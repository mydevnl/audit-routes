<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Routes\RouteFactory;
use MyDev\AuditRoutes\Traits\IgnoresRoutes;

class AuditRoutes
{
    use IgnoresRoutes;

    /** @var int $benchmark */
    protected int $benchmark;

    /** @var array<int, RouteInterface> $routes */
    protected array $routes;

    /**
     * @param iterable<int | string, mixed> $routes
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function __construct(iterable $routes)
    {
        $this->routes = RouteFactory::collection($routes);
        $this->benchmark = Config::integer('audit-routes.benchmark', 0);
        $this->defaultIgnoredRoutes = Config::array('audit-routes.ignored-routes', []);
    }

    /**
     * @param iterable<int | string, mixed> $routes
     * @return self
     */
    public static function for(iterable $routes): self
    {
        return new self($routes);
    }

    /**
     * @param  array<class-string<AuditorInterface>, int> | array<int, AuditorInterface|class-string<AuditorInterface>> $auditors
     *
     * @throws InvalidArgumentException
     *
     * @return AuditedRouteCollection
     */
    public function run(array $auditors): AuditedRouteCollection
    {
        $collection = new AuditedRouteCollection();

        foreach ($this->routes as $route) {
            if (!$this->validateRoute($route)) {
                continue;
            }

            $collection->push(AuditedRoute::for($route, $this->benchmark)->audit($auditors));
        }

        return $collection;
    }

    /**
     * @param int $benchmark
     * @return self
     */
    public function setBenchmark(int $benchmark): self
    {
        $this->benchmark = $benchmark;

        return $this;
    }
}
