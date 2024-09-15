<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRouteCollection;

interface OutputInterface
{
    /**
     * @param AuditedRouteCollection $auditedRoutes
     * @return void
     */
    public function generate(AuditedRouteCollection $auditedRoutes): void;
}
