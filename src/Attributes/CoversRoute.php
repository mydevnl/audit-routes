<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class CoversRoute
{
    /** @var array<int|string, string> $routes */
    protected array $routes;

    /** @param string ...$routes */
    public function __construct(string ...$routes)
    {
        $this->routes = $routes;
    }

    /** @return array<int|string, string> */
    public function routes(): array
    {
        return $this->routes;
    }
}
