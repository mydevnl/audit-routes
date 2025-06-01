<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Actions;

use MyDev\AuditRoutes\Actions\CollectCoversRouteAttribute;
use MyDev\AuditRoutes\Attributes\CoversRoute;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class CollectCoversRouteAttributeTest extends TestCase
{
    #[Test]
    public function it_collects_routes_defined_by_class_attribute(): void
    {
        $class = new #[CoversRoute('api.user.show', 'api.post.index')] class {
        };

        $collectedRoutes = CollectCoversRouteAttribute::run(new ReflectionClass($class));

        $this->assertEquals(['api.user.show', 'api.post.index'], $collectedRoutes);
    }

    #[Test]
    public function it_collects_routes_defined_by_method_attribute(): void
    {
        $class = new class () {
            #[CoversRoute('api.user.show', 'api.post.index')]
            public function method(): void
            {
            }
        };

        try {
            $collectedRoutes = CollectCoversRouteAttribute::run(new ReflectionMethod($class, 'method'));
        } catch (ReflectionException) {
            $collectedRoutes = null;
        }

        $this->assertEquals(['api.user.show', 'api.post.index'], $collectedRoutes);
    }
}
