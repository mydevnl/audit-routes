<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use InvalidArgumentException;
use Symfony\Component\Console\Style\OutputStyle;

class ExportFactory
{
    /**
     * @param OutputStyle $output
     * @return void
     */
    public function __construct(protected OutputStyle $output) {}

    /**
     * @param OutputStyle $output
     * @return self
     */
    public static function channel(OutputStyle $output): self
    {
        return new self($output);
    }

    /**
     * @param null | bool | string | ExportInterface $exporter
     * @param null | string $filename
     * @return null | ExportInterface
     */
    public function build(null | bool | string | ExportInterface $exporter, ?string $filename = null): ?ExportInterface
    {
        $exporter = match (true) {
            empty($exporter)     => null,
            is_bool($exporter)   => $this->stringToExporter('html'),
            is_string($exporter) => $this->stringToExporter($exporter),
            default              => $exporter,
        };

        return $exporter?->setFilename($filename);
    }

    /**
     * @param string $exporter
     * @return ExportInterface
     */
    protected function stringToExporter(string $exporter): ExportInterface
    {
        return match ($exporter) {
            'html'  => new HtmlExport($this->output),
            'json'  => new JsonExport($this->output),
            default => throw new InvalidArgumentException('Invalid exporter provided: ' . $exporter),
        };
    }
}
