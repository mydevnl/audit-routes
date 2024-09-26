<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

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

    /**
     * @param null | bool | ExportInterface | class-string<ExportInterface> $exporter
     * @param array<int, \MyDev\AuditRoutes\Aggregators\AggregatorInterface> $aggregators
     * @return self
     */
    public function withExporter(null | bool | string | ExportInterface $exporter, array $aggregators = []): self
    {
        $this->exporter = match (true) {
            empty($exporter)     => null,
            is_bool($exporter)   => new JsonOutput($this->output),
            is_string($exporter) => new $exporter($this->output),
            default              => $exporter,
        };
        $this->exporter->setAggregators($aggregators);

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
