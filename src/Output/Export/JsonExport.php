<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output\Export;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Contracts\ExportInterface;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Entities\ExportResult;
use MyDev\AuditRoutes\Enums\ExitCode;
use MyDev\AuditRoutes\Utilities\Cast;
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
    public function __construct(protected OutputStyle $output)
    {
        $this->filename = $this->defaultFilename;
    }

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return ExitCode
     */
    public function generate(AuditedRouteCollection $auditedRoutes): ExitCode
    {
        $path = Cast::string(Config::get('audit-routes.output.directory'));
        $fullPath = $path . DIRECTORY_SEPARATOR . $this->filename;

        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        file_put_contents($fullPath, $this->getOutput($auditedRoutes));

        $this->output->section("Report exported to: {$fullPath}");

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
