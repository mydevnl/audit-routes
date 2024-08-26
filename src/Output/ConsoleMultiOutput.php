<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleMultiOutput implements OutputInterface
{
    /** @param array<int, class-string<OutputInterface>> $outputGenerators */
    public function __construct(protected array $outputGenerators, protected OutputStyle $output)
    {
    }

    /** @param array<int, AuditedRoute> $auditedRoutes */
    public function generate(array $auditedRoutes): void
    {
        foreach ($this->outputGenerators as $outputGenerator) {
            (new $outputGenerator($this->output))->generate($auditedRoutes);
        }
    }
}
