<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use Symfony\Component\Console\Style\OutputStyle;

class OutputFactory
{
    /** @var null | class-string<ExportInterface> $exporter */
    protected ?string $exporter = null;

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

    /**
     * @param null | bool | class-string<ExportInterface> $exporter
     * @return self
     */
    public function withExporter(null | bool | string $exporter): self
    {
        if (is_bool($exporter)) {
            $exporter = $exporter ? JsonOutput::class : null;
        }

        $this->exporter = $exporter;

        return $this;
    }

    /** @return OutputInterface */
    public function build(): OutputInterface
    {
        $channels = match ($this->output->getVerbosity()) {
            32      => [ConsoleStatus::class],
            64      => [ConsoleResult::class],
            128     => [ConsoleTable::class],
            256     => [ConsoleTable::class, ConsoleResult::class],
            default => [ConsoleStatus::class],
        };

        if ($this->exporter) {
            $channels[] = $this->exporter;
        }

        return new ConsoleMultiOutput($channels, $this->output);
    }
}
