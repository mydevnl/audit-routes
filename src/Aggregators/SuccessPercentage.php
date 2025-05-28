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
        parent::__construct();

        $this->setName($name);
    }

    /** @return void */
    public function after(): void
    {
        $this->result = 100 - $this->result;
    }
}
