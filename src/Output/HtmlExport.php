<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;

class HtmlExport extends JsonExport
{
    /** @var string $defaultFilename */
    protected string $defaultFilename = 'report.html';

    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @throws Exception
     * @return string
     */
    protected function getOutput(AuditedRouteCollection $auditedRoutes): string
    {
        /** @var ?string $template */
        $template = Config::get('audit-routes.output.html-template');

        if (is_null($template)) {
            throw new Exception('Html output template has nog been configered.');
        }

        return View::make($template, ['json' => parent::getOutput($auditedRoutes)])->render();
    }
}
