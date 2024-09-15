<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\AuditStatus;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleStatus implements OutputInterface
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
        if ($auditedRoutes->where('status', AuditStatus::Failed->value)->isNotEmpty()) {
            $this->output->text('1');

            return;
        }

        $this->output->text('0');
    }
}
