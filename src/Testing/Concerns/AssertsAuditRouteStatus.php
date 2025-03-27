<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Testing\Concerns;

use Closure;
use MyDev\AuditRoutes\Auditors\AuditorInterface;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
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
     * @param string | Closure(AuditedRouteCollection): string                                                       $message
     * @param array<int, string>                                                                                     $ignoredRoutes
     * @param int                                                                                                    $benchmark
     * @return static
     */
    protected function assertAuditRoutesOk(
        iterable $routes,
        array $auditors,
        string | Closure $message,
        array $ignoredRoutes = [],
        int $benchmark = 0,
    ): static {
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
     * @param AuditedRouteCollection $auditedRoutes
     * @param string | Closure(AuditedRouteCollection): void $message
     * @return Callback
     */
    protected function noAuditRouteHasFailed(AuditedRouteCollection $auditedRoutes, string | Closure $message): Callback
    {
        return Assert::callback(function () use ($auditedRoutes, $message): bool {
            $failedRoutes = $auditedRoutes->where('status', AuditStatus::Failed->value);

            if ($failedRoutes->isEmpty()) {
                return true;
            }

            if (is_callable($message)) {
                $message = $message($failedRoutes);
            }

            Assert::fail($message);
        });
    }
}
