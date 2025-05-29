<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\ExitCode;

interface OutputInterface
{
    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return ExitCode
     */
    public function generate(AuditedRouteCollection $auditedRoutes): ExitCode;
}
