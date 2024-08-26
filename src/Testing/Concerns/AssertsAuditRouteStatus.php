<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Testing\Concerns;

use Closure;
use MyDev\AuditRoutes\Auditors\AuditorInterface;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Enums\AuditStatus;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Callback;

trait AssertsAuditRouteStatus
{
    /**
     * Run provided auditors for given routes and assert all have an Ok status.
     *
     * @param iterable                                                                                               $routes
     * @param array<class-string<AuditorInterface>, int>|array<int, AuditorInterface|class-string<AuditorInterface>> $auditors
     * @param string | Closure(array<string>): string                                                                $message
     * @param array<int, string>                                                                                     $ignoredRoutes
     */
    protected function assertAuditRoutesOk(
        iterable $routes,
        array $auditors,
        string | Closure $message,
        array $ignoredRoutes = [],
        int $benchmark = 0,
    ): self {
        $auditedRoutes = AuditRoutes::for($routes)
            ->setBenchmark($benchmark)
            ->ignoreRoutes($ignoredRoutes)
            ->run($auditors);

        Assert::assertThat($routes, $this->noAuditRouteHasFailed($auditedRoutes, $message));

        return $this;
    }

    /**
     * Callback for asserting none of the audited routes have a failed status.
     *
     * @param array<int, \MyDev\AuditRoutes\Entities\AuditedRoute> $auditedRoutes
     */
    protected function noAuditRouteHasFailed(array $auditedRoutes, string | callable $message): Callback
    {
        return Assert::callback(function () use ($auditedRoutes, $message): bool {
            $failedRoutes = [];
            foreach ($auditedRoutes as $auditedRoute) {
                if ($auditedRoute->hasStatus(AuditStatus::Failed)) {
                    $failedRoutes[] = $auditedRoute->getName();
                }
            }

            if ($failedRoutes === []) {
                return true;
            }

            if (is_callable($message)) {
                $message = $message($failedRoutes);
            }

            Assert::fail($message);
        });
    }
}
