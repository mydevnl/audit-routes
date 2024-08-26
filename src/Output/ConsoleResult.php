<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Traits\TracksTime;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleResult implements OutputInterface
{
    use TracksTime;

    public function __construct(protected OutputStyle $output)
    {
        $this->startTime();
    }

    /** @param array<int, AuditedRoute> $auditedRoutes */
    public function generate(array $auditedRoutes): void
    {
        $this->printResult($auditedRoutes);
    }

    /** @param array<int, AuditedRoute> $auditedRoutes */
    protected function printResult(array $auditedRoutes): void
    {
        $failedCount = $this->getFailedCount($auditedRoutes);

        $output = [
            "{$failedCount} routes scored below the benchmark",
            "Total execution time: {$this->stopTime()} seconds",
        ];

        if ($failedCount) {
            $this->output->error($output);

            return;
        }

        $this->output->success($output);
    }

    /** @param array<int, AuditedRoute> $auditedRoutes */
    protected function getFailedCount(array $auditedRoutes): int
    {
        $failedCount = 0;

        foreach ($auditedRoutes as $auditedRoute) {
            if ($auditedRoute->hasStatus(AuditStatus::Failed)) {
                $failedCount++;
            }
        }

        return $failedCount;
    }
}
