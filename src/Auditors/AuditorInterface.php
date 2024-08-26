<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use MyDev\AuditRoutes\Repositories\RouteInterface;

interface AuditorInterface
{
    public static function make(?array $arguments = null): self;

    public function run(RouteInterface $route): int;

    public function handle(RouteInterface $route): int;

    public function getScore(int $score): int;

    public function setWeight(int $weight): self;

    public function setPenalty(int $penalty): self;

    public function setLimit(int $limit): self;
}
