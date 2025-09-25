<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;

/**
 * Scoped Binding Security Auditor.
 *
 * Ensures routes with multiple route parameters use Laravel scoped bindings
 * to prevent unauthorized access to related models.
 *
 * Scoring:
 * - Routes with scoped bindings enabled: +2 (multiplied by weight)
 * - Routes with single parameter or no scoped binding support: +1 (neutral/not applicable)
 * - Routes with multiple parameters but no scoped bindings: 0 (or the penalty value if set)
 *
 * Configuration:
 * - No arguments required
 * - Automatically detects routes with multiple parameters in URI
 * - Checks Laravel's scoped binding configuration
 * - Can be configured with conditional auditing and route ignoring patterns
 *
 * @example
 * ScopedBindingAuditor::make()->setWeight(20)->setPenalty(-100)
 * @example
 * ScopedBindingAuditor::make()->when(fn($route) => str_contains($route->getUri(), '{team}'))
 */
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

        preg_match_all('/{(.*?)}/', $route->getUri(), $bindings);

        if (count($bindings[0]) > 1) {
            return $this->getScore(self::FAIL);
        }

        return $this->getScore(self::NOT_APPLICABLE);
    }
}
