<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output\Export;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use MyDev\AuditRoutes\Aggregators\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Entities\ExportResult;
use MyDev\AuditRoutes\Enums\ExitCode;
use Symfony\Component\Console\Style\OutputStyle;

class JsonExport implements ExportInterface
{
    /** @var array<int, AggregatorInterface> $aggregators */
    protected array $aggregators = [];

    /** @var string $defaultFilename */
    protected string $defaultFilename = 'report.json';

    /** @var string $filename */
    protected string $filename;

    /**
     * @param OutputStyle $output
     * @return void
     */
    public function __construct(protected OutputStyle $output) {
        $this->filename = $this->defaultFilename;
    }

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return ExitCode
     */
    public function generate(AuditedRouteCollection $auditedRoutes): ExitCode
    {
        $path = Config::string('audit-routes.output.directory');
        $fullPath = $path . DIRECTORY_SEPARATOR . $this->filename;

        if (!Storage::directoryExists($path)) {
            Storage::createDirectory($path);
        }

        Storage::put($fullPath, $this->getOutput($auditedRoutes));

        $this->output->section('Report exported to: ' . Storage::path($fullPath));

        return ExitCode::Success;
    }

    /**
     * @param array<int, AggregatorInterface> $aggregators
     * @return self
     */
    public function setAggregators(array $aggregators): self
    {
        $this->aggregators = $aggregators;

        return $this;
    }

    /**
     * @param null | string $filename
     * @return self
     */
    public function setFilename(?string $filename): self
    {
        $this->filename = $filename ?? $this->defaultFilename;

        return $this;
    }

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return string
     */
    protected function getOutput(AuditedRouteCollection $auditedRoutes): string
    {
        $result = new ExportResult(
            aggregates: $auditedRoutes->aggregate(...$this->aggregators),
            routes: $auditedRoutes->sort()->get(),
        );

        return (string) json_encode($result, JSON_PRETTY_PRINT);
    }
}
