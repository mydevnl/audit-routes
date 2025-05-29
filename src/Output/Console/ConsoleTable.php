<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output\Console;

use MyDev\AuditRoutes\Contracts\OutputInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Enums\ExitCode;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleTable implements OutputInterface
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
        $this->output->table([
            'Status',
            'Route',
            'Score',
        ], array_map(fn (AuditedRoute $auditedRoute): array => [
            $auditedRoute->hasStatus(AuditStatus::Failed) ? '✖' : '✓',
            $auditedRoute->getDisplayName(),
            $auditedRoute->getScore(),
        ], $auditedRoutes->sort()->get()));

        return ExitCode::Success;
    }
}
