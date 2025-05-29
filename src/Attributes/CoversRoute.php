<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class CoversRoute
{
    /**
     * @param string | array<int, string> $routes
     * @return void
     */
    public function __construct(protected string|array $routes)
    {
    }

    /** @return string | array<int, string> */
    public function routes(): string | array
    {
        return $this->routes;
    }
}
