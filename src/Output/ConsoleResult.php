<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Traits\TracksTime;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleResult implements OutputInterface
{
    use TracksTime;

    /**
     * @param OutputStyle $output
     * @return void
     */
    public function __construct(protected OutputStyle $output)
    {
        $this->startTime();
    }

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return void
     */
    public function generate(AuditedRouteCollection $auditedRoutes): void
    {
        $failedCount = $auditedRoutes->where('status', AuditStatus::Failed->value)->count();

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
}
