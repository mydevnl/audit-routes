<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Enums\AuditStatus;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleTable implements OutputInterface
{
    public function __construct(protected OutputStyle $output)
    {
    }

    /** @param array<int, AuditedRoute> $auditedRoutes */
    public function generate(array $auditedRoutes): void
    {
        uasort($auditedRoutes, function (AuditedRoute $a, AuditedRoute $b): int {
            if ($a->getScore() === $b->getScore()) {
                return strval($a) < strval($b) ? -1 : 1;
            }

            return $a->getScore() < $b->getScore() ? -1 : 1;
        });

        $this->output->table([
            'Status',
            'Route',
            'Score',
        ], array_map(fn (AuditedRoute $route): array => [
            $route->hasStatus(AuditStatus::Failed) ? '✖' : '✓',
            $route->getName(),
            $route->getScore(),
        ], $auditedRoutes));
    }
}
