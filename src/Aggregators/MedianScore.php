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

    /** @return string */
    public function getName(): string
    {
        return 'Median score';
    }

    /** @return float */
    public function getResult(): float
    {
        if (!is_null($this->result)) {
            return $this->result;
        }

        ksort($this->visitedScores);
        $this->result = $this->getMedian($this->visitedCount / 2);

        return $this->result;
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
