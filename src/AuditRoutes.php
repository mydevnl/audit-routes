<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use MyDev\AuditRoutes\Auditors\AuditorFactory;
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Routes\RouteFactory;
use MyDev\AuditRoutes\Traits\IgnoresRoutes;
use MyDev\AuditRoutes\Utilities\Cast;

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
     */
    public function __construct(iterable $routes)
    {
        $this->routes = RouteFactory::collection($routes);

        $this->benchmark = Cast::int(Config::get('audit-routes.benchmark'));

        /** @var array<int, string> $ignoredRoutes */
        $ignoredRoutes = Cast::array(Config::get('audit-routes.ignored-routes'));
        $this->defaultIgnoredRoutes = $ignoredRoutes;
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
     * @return AuditedRouteCollection
     *
     * @throws InvalidArgumentException
     */
    public function run(array $auditors): AuditedRouteCollection
    {
        $collection = new AuditedRouteCollection();
        $initialisedAuditors = AuditorFactory::buildMany($auditors);

        foreach ($this->routes as $route) {
            if (!$this->validateRoute($route)) {
                continue;
            }

            $auditedRoute = AuditedRoute::for($route, $this->benchmark)->audit($initialisedAuditors);
            $collection->push($auditedRoute);
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
