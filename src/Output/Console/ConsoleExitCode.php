<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output\Console;

use MyDev\AuditRoutes\Contracts\OutputInterface;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Enums\ExitCode;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleExitCode implements OutputInterface
{
    /**
     * @param OutputStyle $output
     * @return void
     */
    public function __construct(protected OutputStyle $output)
    {
    }

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return ExitCode
     */
    public function generate(AuditedRouteCollection $auditedRoutes): ExitCode
    {
        if ($auditedRoutes->where('status', AuditStatus::Failed->value)->isNotEmpty()) {
            return ExitCode::Failure;
        }

        return ExitCode::Success;
    }
}
