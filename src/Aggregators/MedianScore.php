<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

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
    protected ?float $result = null;

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
        $this->result = $this->getMedian($this->visitedCount / 2);
    }

    protected function getMedian(float $target, ?int $previousScore = null): float
    {
        $score = array_key_first($this->visitedScores);

        if ($target <= 1) {
            return (($previousScore ?? $score) + $score) / 2;
        }

        $target -= $this->visitedScores[$score];
        unset($this->visitedScores[$score]);

        if ($target <= 0) {
            return $score;
        }

        return $this->getMedian($target, $score);
    }
}
