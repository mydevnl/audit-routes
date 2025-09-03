<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

use Closure;
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

    /** @return int */
    public function getWeight(): int;

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

    /** @param array<int, string> $routes */
    public function ignoreRoutes(array $routes): self;

    /**
     * @param Closure(RouteInterface): bool $condition
     * @return static
     */
    public function when(Closure $condition): static;

    /**
     * @param null | string $name
     * @return static
     */
    public function setName(?string $name): static;

    /** @return null | string */
    public function getName(): ?string;
}
