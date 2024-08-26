<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use MyDev\AuditRoutes\Entities\AuditedRoute;

interface OutputInterface
{
    /** @param array<int, AuditedRoute> $auditedRoutes */
    public function generate(array $auditedRoutes): void;
}
