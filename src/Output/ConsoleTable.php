<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\AuditStatus;
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
     * @return void
     */
    public function generate(AuditedRouteCollection $auditedRoutes): void
    {
        $this->output->table([
            'Status',
            'Route',
            'Score',
        ], array_map(fn (AuditedRoute $route): array => [
            $route->hasStatus(AuditStatus::Failed) ? '✖' : '✓',
            $route->getName(),
            $route->getScore(),
        ], $auditedRoutes->sort()->get()));
    }
}
