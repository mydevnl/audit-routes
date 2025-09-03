<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit;

use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class AuditRoutesTest extends TestCase
{
    #[Test]
    public function it_traverses_over_each_provided_route(): void
    {
        $routes = ['route.1', 'route.2', 'route.3'];

        $result = AuditRoutes::for($routes)->run([]);

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(AuditedRoute::class, $result);
    }

    #[Test]
    public function it_utilizes_the_provided_benchmark(): void
    {
        $routes = ['route.1', 'route.2', 'route.3'];
        $auditRoutes = AuditRoutes::for($routes);

        $okResult = $auditRoutes->setBenchmark(-1)->run([]);
        $failedResult = $auditRoutes->setBenchmark(1)->run([]);

        $this->assertTrue($okResult->where('status', AuditStatus::Ok->value)->isNotEmpty());
        $this->assertTrue($okResult->where('status', AuditStatus::Failed->value)->isEmpty());
        $this->assertTrue($failedResult->where('status', AuditStatus::Ok->value)->isEmpty());
        $this->assertTrue($failedResult->where('status', AuditStatus::Failed->value)->isNotEmpty());
    }

    #[DataProvider('ignoredRouteDataProvider')]
    #[Test]
    public function it_does_not_traverse_ignored_routes(array $routes, array $filters): void
    {
        $result = AuditRoutes::for(array_keys($routes))->ignoreRoutes($filters)->run([]);

        $expectedCount = count(array_filter($routes));
        $this->assertCount($expectedCount, $result);
    }

    public static function ignoredRouteDataProvider(): array
    {
        return [
            [['api.user.show' => false, 'api.post.index' => true], ['api.user.show']],
            [['api.user.show' => false, 'api.post.index' => true], ['*.show']],
            [['api.user.show' => false, 'api.post.index' => true], ['api.user.*']],
            [['api.user.show' => false, 'api.post.index' => true], ['*.user.*']],
            [['api.user.show' => false, 'api.post.index' => true], ['api.*.show']],
        ];
    }
}
