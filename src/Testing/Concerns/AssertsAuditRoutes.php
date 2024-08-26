<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Testing\Concerns;

trait AssertsAuditRoutes
{
    use AssertsRouteMiddleware;
    use AssertsRouteTested;
}
