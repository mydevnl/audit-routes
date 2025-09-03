<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Entities;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Enums\AuditStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class AuditedRouteTest extends TestCase
{
    #[Test]
    public function it_creates_instance_via_constructor(): void
    {
        $benchmark = random_int(1, 100);
        $route = $this->mockRoute();
        $auditedRoute = AuditedRoute::for($route, $benchmark);

        $this->assertInstanceOf(AuditedRoute::class, $auditedRoute);
        $this->assertSame($benchmark, $auditedRoute->getBenchmark());
    }

    #[Test]
    public function it_creates_instance_via_factory(): void
    {
        $benchmark = random_int(1, 100);
        $route = $this->mockRoute();
        $auditedRoute = new AuditedRoute($route, $benchmark);

        $this->assertSame($benchmark, $auditedRoute->getBenchmark());
    }

    #[Test]
    public function it_uses_the_route_name_if_available_as_display_name(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute('my.route'));

        $this->assertSame('my.route', $auditedRoute->getDisplayName());
    }

    #[Test]
    public function it_falls_back_to_uri_if_route_name_is_unavailable(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute(null, 'foo'));

        $this->assertSame('/foo', $auditedRoute->getDisplayName());
    }

    #[Test]
    public function it_prefixes_uri_with_slash_if_missing(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute(null, 'api/users'));

        $this->assertSame('/api/users', $auditedRoute->getDisplayName());
    }

    #[Test]
    public function it_does_not_double_prefix_uri(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute(null, '/api/users'));

        $this->assertSame('/api/users', $auditedRoute->getDisplayName());
    }

    #[Test]
    public function it_resets_the_score_before_each_audit(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute());

        $auditedRoute->audit([$this->mockAuditor(5)]);
        $auditedRoute->audit([$this->mockAuditor(2)]);

        $this->assertSame(2, $auditedRoute->getScore());
    }

    #[Test]
    public function it_skips_auditor_results_which_are_null(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute());

        $auditedRoute->audit([$this->mockAuditor(null)]);

        $this->assertSame(0, $auditedRoute->getScore());
        $this->assertEmpty($auditedRoute->toArray()['auditors']);
    }

    #[Test]
    public function it_accumulates_scores_from_auditors(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute());

        $auditedRoute->audit([
            $this->mockAuditor(5),
            $this->mockAuditor(10),
        ]);

        $this->assertSame(15, $auditedRoute->getScore());
    }

    #[Test]
    public function it_stores_results_in_results_collection(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute());

        $auditedRoute->audit([
            $this->mockAuditor(5),
            $this->mockAuditor(10),
        ]);

        $results = $auditedRoute->toArray();
        $this->assertIsArray($results['auditors']);
        $this->assertCount(2, $results['auditors']);
    }

    #[Test]
    public function it_has_a_failed_status_if_the_score_is_below_the_benchmark(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute(), 10);

        $auditedRoute->audit([$this->mockAuditor(9)]);

        $this->assertSame(AuditStatus::Failed, $auditedRoute->getStatus());
    }

    #[Test]
    #[TestWith([10, 11])]
    public function it_has_an_ok_status_if_the_score_meets_or_exceeds_benchmark(int $score): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute(), 10);

        $auditedRoute->audit([$this->mockAuditor($score)]);

        $this->assertSame(AuditStatus::Ok, $auditedRoute->getStatus());
    }

    #[Test]
    public function it_correctly_confirms_its_own_status(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute(), 5);

        $auditedRoute->audit([$this->mockAuditor(5)]);

        $this->assertTrue($auditedRoute->hasStatus(AuditStatus::Ok));
        $this->assertFalse($auditedRoute->hasStatus(AuditStatus::Failed));
    }

    #[Test]
    public function it_casts_to_string_using_it_display_name(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute('route.name'));

        $this->assertSame('route.name', (string) $auditedRoute);
    }

    #[Test]
    public function it_serializes_to_array_with_expected_keys(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute('route.name'), 1);

        $auditedRoute->audit([$this->mockAuditor(1)]);

        $array = $auditedRoute->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('score', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('failed', $array);
        $this->assertArrayHasKey('benchmark', $array);
        $this->assertArrayHasKey('auditors', $array);
    }

    #[Test]
    public function it_can_be_serializes_to_json(): void
    {
        $auditedRoute = AuditedRoute::for($this->mockRoute('route.name'), 1);

        $auditedRoute->audit([$this->mockAuditor(1)]);

        $json = json_encode($auditedRoute);
        $this->assertIsString($json);
        $this->assertJson($json);
        $this->assertStringContainsString('"route.name"', $json);
    }

    /**
     * @param null | string $name
     * @param string $uri
     * @return RouteInterface
     */
    private function mockRoute(?string $name = null, string $uri = '/test'): RouteInterface
    {
        try {
            $route = $this->createMock(RouteInterface::class);
        } catch (Exception) {
            $this->fail('Failed to create route mock.');
        }

        $route->method('getName')->willReturn($name);
        $route->method('getUri')->willReturn($uri);

        return $route;
    }

    /**
     * @param int|null $result
     * @return AuditorInterface
     */
    private function mockAuditor(?int $result): AuditorInterface
    {
        try {
            $auditor = $this->createMock(AuditorInterface::class);
        } catch (Exception) {
            $this->fail('Failed to create auditor mock.');
        }

        $auditor->method('run')->willReturn($result);

        return $auditor;
    }
}
