<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Auditors\AuditorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Repositories\RouteFactory;
use MyDev\AuditRoutes\Repositories\RouteInterface;
use MyDev\AuditRoutes\Traits\IgnoresRoutes;

class AuditRoutes
{
    use IgnoresRoutes;

    protected int $benchmark;

    /** @var array<int, RouteInterface> $routes */
    protected array $routes;

    public function __construct(iterable $routes)
    {
        $this->routes = RouteFactory::collection($routes);
        $this->benchmark = Config::get('audit-routes.benchmark', 0);
        $this->defaultIgnoredRoutes = Config::get('audit-routes.ignored-routes', []);
    }

    public static function for(iterable $routes): self
    {
        return new self($routes);
    }

    /**
     * @param  array<class-string<AuditorInterface>, int> | array<int, AuditorInterface|class-string<AuditorInterface>> $auditors
     * @return array<int, AuditedRoute>
     */
    public function run(array $auditors): array
    {
        $auditedRoutes = [];

        foreach ($this->routes as $route) {
            if (!$this->validateRoute($route)) {
                continue;
            }

            $auditedRoutes[] = AuditedRoute::for($route, $this->benchmark)->audit($auditors);
        }

        return $auditedRoutes;
    }

    public function setBenchmark(int $benchmark): self
    {
        $this->benchmark = $benchmark;

        return $this;
    }
}
