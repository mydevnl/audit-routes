<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Contracts\OutputInterface;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\ExitCode;
use Symfony\Component\Console\Style\OutputStyle;

class OutputCollection implements OutputInterface
{
    /**
     * @param array<int, OutputInterface | class-string<OutputInterface>> $outputGenerators
     * @param OutputStyle $output
     * @return void
     */
    public function __construct(protected array $outputGenerators, protected OutputStyle $output)
    {
    }

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return ExitCode
     */
    public function generate(AuditedRouteCollection $auditedRoutes): ExitCode
    {
        $exitCode = ExitCode::Success;

        foreach ($this->outputGenerators as $outputGenerator) {
            if (is_string($outputGenerator)) {
                $outputGenerator = new $outputGenerator($this->output);
            }

            $currentExitCode = $outputGenerator->generate($auditedRoutes);

            if ($currentExitCode !== ExitCode::Success) {
                $exitCode = $currentExitCode;
            }
        }

        return $exitCode;
    }
}
