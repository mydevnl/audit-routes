<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class MedianScore implements AggregatorInterface
{
    use Aggregateable;

    /** @var array<int, int> $visitedScores */
    protected array $visitedScores = [];

    /** @var int $visitedCount */
    protected int $visitedCount = 0;

    /** @var float $result */
    protected float $result = 0;

    /**
     * @param null | string $name
     * @return void
     */
    public function __construct(?string $name = null)
    {
        $this->setName($name);
    }

    /**
     * @param AuditedRoute $auditedRoute
     * @return void
     */
    public function visit(AuditedRoute $auditedRoute): void
    {
        if (!isset($this->visitedScores[$auditedRoute->getScore()])) {
            $this->visitedScores[$auditedRoute->getScore()] = 0;
        }

        $this->visitedScores[$auditedRoute->getScore()]++;
        $this->visitedCount++;
    }

    /** @return void */
    public function after(): void
    {
        ksort($this->visitedScores);

        $leftMedian = null;
        $leftIndex = (int) floor(($this->visitedCount - 1) / 2);
        $rightIndex = (int) ceil(($this->visitedCount - 1) / 2);

        $cumulative = 0;
        foreach ($this->visitedScores as $value => $count) {
            $cumulative += $count;

            if (is_null($leftMedian) && $cumulative > $leftIndex) {
                $leftMedian = $value;
            }

            if ($cumulative > $rightIndex) {
                $this->result = ($leftMedian + $value) / 2;

                return;
            }
        }
    }
}
