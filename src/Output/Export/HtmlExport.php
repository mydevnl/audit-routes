<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output\Export;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Utilities\Cast;

class HtmlExport extends JsonExport
{
    /** @var string $defaultFilename */
    protected string $defaultFilename = 'report.html';

    /**
     * @param AuditedRouteCollection $auditedRoutes
     *
     * @throws Exception
     *
     * @return string
     */
    protected function getOutput(AuditedRouteCollection $auditedRoutes): string
    {
        $template = Cast::string(Config::get('audit-routes.output.html-report-template'));

        if (empty($template)) {
            throw new Exception('Html output template has not been configured.');
        }

        return View::make($template, ['json' => parent::getOutput($auditedRoutes)])->render();
    }
}
