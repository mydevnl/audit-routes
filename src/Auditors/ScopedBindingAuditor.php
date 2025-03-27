<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Routes\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class ScopedBindingAuditor implements AuditorInterface
{
    use Auditable;

    protected const Ok = 2;
    protected const NotApplicable = 1;
    protected const Fail = 0;

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        if ($route->hasScopedBindings()) {
            return $this->getScore(self::Ok);
        }

        if (is_null($route->hasScopedBindings())) {
            return $this->getScore(self::NotApplicable);
        }

        preg_match_all("/{(.*?)}/", $route->getUri(), $bindings);

        if (count($bindings[0]) > 1) {
            return $this->getScore(self::Fail);
        }

        return $this->getScore(self::NotApplicable);
    }
}
