<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use Symfony\Component\Console\Style\OutputStyle;

class JsonOutput implements ExportInterface
{
    /** @var array<int, \MyDev\AuditRoutes\Aggregators\AggregatorInterface> $aggregators */
    protected array $aggregators = [];

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
        $path = strval(Config::get('audit-routes.output.directory'));
        $filename = 'report.json';
        $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

        if (!Storage::directoryExists($path)) {
            Storage::createDirectory($path);
        }

        $result = [
            'totals' => $auditedRoutes->aggregate(...$this->aggregators),
            'routes' => $auditedRoutes->sort()->get(),
        ];

        $json = json_encode($result, JSON_PRETTY_PRINT);

        Storage::put($fullPath, $json);

        $this->output->section('Report exported to: ' . Storage::path($fullPath));
    }

    /** @param array<int, \MyDev\AuditRoutes\Aggregators\AggregatorInterface> $aggregators */
    public function setAggregators(array $aggregators): self
    {
        $this->aggregators = $aggregators;

        return $this;
    }
}
