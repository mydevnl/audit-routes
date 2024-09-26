<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;

class SuccessPercentage extends FailedPercentage
{
    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        parent::visit($auditedRoute);
        $this->result = 100 - $this->result;
    }

    /** @return string */
    public function getName(): string
    {
        return 'Success percentage';
    }
}
