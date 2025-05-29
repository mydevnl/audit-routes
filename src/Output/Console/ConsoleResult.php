<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output\Console;

use MyDev\AuditRoutes\Contracts\OutputInterface;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Enums\ExitCode;
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
     * @return ExitCode
     */
    public function generate(AuditedRouteCollection $auditedRoutes): ExitCode
    {
        $failedCount = $auditedRoutes->where('status', AuditStatus::Failed->value)->count();

        $output = [
            "{$failedCount}/{$auditedRoutes->count()} routes scored below the benchmark",
            "Total execution time: {$this->stopTime()} seconds",
        ];

        if ($failedCount) {
            $this->output->error($output);

            return ExitCode::Failure;
        }

        $this->output->success($output);

        return ExitCode::Success;
    }
}
