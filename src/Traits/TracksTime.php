<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

trait TracksTime
{
    protected float $startTime = 0;

    /** @return float */
    public function startTime(): float
    {
        $this->startTime = microtime(true);

        return $this->startTime;
    }

    /** @return float */
    public function getTime(): float
    {
        return (microtime(true) - $this->startTime) / 60;
    }

    /** @return float */
    public function stopTime(): float
    {
        $time = $this->getTime();
        $this->startTime = 0;

        return $time;
    }
}
