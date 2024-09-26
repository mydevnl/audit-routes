<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class ModeScore implements AggregatorInterface
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
        return 'Mode score';
    }

    /** @return float */
    public function getResult(): float
    {
        if (!is_null($this->result)) {
            return $this->result;
        }

        asort($this->visitedScores);

        $this->result = array_key_last($this->visitedScores);

        return $this->result;
    }
}
