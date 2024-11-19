<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use JsonSerializable;
use MyDev\AuditRoutes\Routes\RouteInterface;

interface AuditorInterface extends JsonSerializable
{
    /**
     * @param null | array $arguments
     * @return self
     */
    public static function make(?array $arguments = null): self;

    /**
     * @param RouteInterface $route
     * @return ?int
     */
    public function run(RouteInterface $route): ?int;

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int;

    /**
     * @param int $score
     * @return int
     */
    public function getScore(int $score): int;

    /**
     * @param int $weight
     * @return self
     */
    public function setWeight(int $weight): self;

    /**
     * @param int $penalty
     * @return self
     */
    public function setPenalty(int $penalty): self;

    /**
     * @param int $limit
     * @return self
     */
    public function setLimit(int $limit): self;
}
