<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Contracts\ExportInterface;
use MyDev\AuditRoutes\Contracts\OutputInterface;
use MyDev\AuditRoutes\Output\Console\ConsoleExitCode;
use MyDev\AuditRoutes\Output\Console\ConsoleResult;
use MyDev\AuditRoutes\Output\Console\ConsoleTable;
use Symfony\Component\Console\Style\OutputStyle;

class OutputFactory
{
    /** @var null | ExportInterface $exporter */
    protected ?ExportInterface $exporter = null;

    /**
     * @param OutputStyle $output
     * @return void
     */
    public function __construct(protected OutputStyle $output)
    {
    }

    /**
     * @param OutputStyle $output
     * @return self
     */
    public static function channel(OutputStyle $output): self
    {
        return new self($output);
    }

    /** @return OutputInterface */
    public function build(): OutputInterface
    {
        $verbosity = $this->output->getVerbosity();

        if ($this->output->isQuiet()) {
            $verbosity = null;
        }

        $channels = match ($verbosity) {
            32 => [ConsoleResult::class],
            64 => [ConsoleTable::class],
            128, 256 => [ConsoleTable::class, ConsoleResult::class],
            default => [ConsoleExitCode::class],
        };

        if ($this->exporter) {
            $channels[] = $this->exporter;
        }

        return new OutputCollection($channels, $this->output);
    }

    /**
     * @param null | ExportInterface $exporter
     * @return self
     */
    public function setExporter(?ExportInterface $exporter): self
    {
        $this->exporter = $exporter;

        return $this;
    }
}
