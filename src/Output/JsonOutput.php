<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use Symfony\Component\Console\Style\OutputStyle;

class JsonOutput implements ExportInterface
{
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

        $json = json_encode($auditedRoutes->sort()->get(), JSON_PRETTY_PRINT);

        if (!Storage::directoryExists($path)) {
            Storage::createDirectory($path);
        }

        Storage::put("{$path}/{$filename}", $json);

        $this->output->section('Report exported to: ' . Storage::path("{$path}/{$filename}"));
    }
}
