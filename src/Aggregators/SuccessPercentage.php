<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

class SuccessPercentage extends FailedPercentage
{
    /**
     * @param null | string $name
     * @return void
     */
    public function __construct(?string $name = null)
    {
        $this->setName($name);

        parent::__construct();
    }

    /** @return void */
    public function after(): void
    {
        $this->result = 100 - $this->result;
    }
}
