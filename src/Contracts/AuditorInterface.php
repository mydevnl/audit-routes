<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

use JsonSerializable;

interface AuditorInterface extends JsonSerializable
{
    /**
     * @param null | array<int | string, mixed> $arguments
     * @return self
     */
    public static function make(?array $arguments = null): self;

    /**
     * @param RouteInterface $route
     * @return null | int
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
     * @return static
     */
    public function setWeight(int $weight): static;

    /**
     * @param int $penalty
     * @return static
     */
    public function setPenalty(int $penalty): static;

    /**
     * @param int $limit
     * @return static
     */
    public function setLimit(int $limit): static;
}
