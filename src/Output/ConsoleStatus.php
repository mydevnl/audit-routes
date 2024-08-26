<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Enums\AuditStatus;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleStatus implements OutputInterface
{
    public function __construct(protected OutputStyle $output)
    {
    }

    /** @param array<int, AuditedRoute> $auditedRoutes */
    public function generate(array $auditedRoutes): void
    {
        foreach ($auditedRoutes as $auditedRoute) {
            if ($auditedRoute->hasStatus(AuditStatus::Failed)) {
                $this->output->text('1');

                return;
            }
        }
        $this->output->text('0');
    }
}
