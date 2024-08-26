<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class CoversRoute
{
    /** @param string | array<int, string> $routes */
    public function __construct(private string|array $routes)
    {
    }

    public function routes(): string
    {
        return $this->routes;
    }
}
