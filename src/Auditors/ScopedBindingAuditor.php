<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Routes\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

class ScopedBindingAuditor implements AuditorInterface
{
    use Auditable;

    protected const OK = 2;
    protected const NOT_APPLICABLE = 1;
    protected const FAIL = 0;

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        if ($route->hasScopedBindings()) {
            return $this->getScore(self::OK);
        }

        if (is_null($route->hasScopedBindings())) {
            return $this->getScore(self::NOT_APPLICABLE);
        }

        preg_match_all("/{(.*?)}/", $route->getUri(), $bindings);

        if (count($bindings[0]) > 1) {
            return $this->getScore(self::FAIL);
        }

        return $this->getScore(self::NOT_APPLICABLE);
    }
}
