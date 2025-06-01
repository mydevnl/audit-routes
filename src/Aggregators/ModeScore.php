<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Aggregators;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Traits\Aggregateable;

class ModeScore implements AggregatorInterface
{
    use Aggregateable;

    /** @var array<int, int> $visitedScores */
    protected array $visitedScores = [];

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
    }

    /** @return void */
    public function after(): void
    {
        if (empty($this->visitedScores)) {
            return;
        }

        ksort($this->visitedScores);
        $modeOccurrences = max($this->visitedScores);

        foreach ($this->visitedScores as $score => $occurrences) {
            if ($occurrences === $modeOccurrences) {
                $this->result = $score;

                return;
            }
        }
    }
}
