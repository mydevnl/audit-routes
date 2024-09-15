<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use Symfony\Component\Console\Style\OutputStyle;

class ConsoleMultiOutput implements OutputInterface
{
    /**
     * @param array<int, class-string<OutputInterface>> $outputGenerators
     * @param OutputStyle $output
     * @return void
     */
    public function __construct(protected array $outputGenerators, protected OutputStyle $output)
    {
    }

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return void
     */
    public function generate(AuditedRouteCollection $auditedRoutes): void
    {
        foreach ($this->outputGenerators as $outputGenerator) {
            (new $outputGenerator($this->output))->generate($auditedRoutes);
        }
    }
}
