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

    /** @var int $visitedCount */
    protected int $visitedCount = 0;

    /** @var null | float $result */
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
        asort($this->visitedScores);

        $this->result = array_key_last($this->visitedScores);
    }
}
