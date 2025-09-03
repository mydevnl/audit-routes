<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Entities;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Entities\ExportResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ExportResultTest extends TestCase
{
    #[Test]
    public function it_constructs_with_aggregates_and_routes(): void
    {
        $aggregates = [$this->mockAggregator()];
        $routes = [$this->mockAuditedRoute()];

        $exportResult = new ExportResult($aggregates, $routes);

        $this->assertSame($aggregates, $exportResult->aggregates);
        $this->assertSame($routes, $exportResult->routes);
    }

    #[Test]
    public function it_exports_to_array_with_correct_keys(): void
    {
        $exportResult = new ExportResult([$this->mockAggregator()], [$this->mockAuditedRoute()]);

        $array = $exportResult->toArray();

        $this->assertArrayHasKey('aggregates', $array);
        $this->assertArrayHasKey('routes', $array);
    }

    #[Test]
    public function it_serializes_to_json_with_correct_structure(): void
    {
        $exportResult = new ExportResult([$this->mockAggregator()], [$this->mockAuditedRoute()]);

        $json = json_encode($exportResult);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('aggregates', $decoded);
        $this->assertArrayHasKey('routes', $decoded);
    }

    /** @return AggregatorInterface */
    private function mockAggregator(): AggregatorInterface
    {
        try {
            $aggregator = $this->createMock(AggregatorInterface::class);
        } catch (Exception) {
            $this->fail('Failed to create aggregator mock.');
        }

        return $aggregator;
    }

    /** @return AuditedRoute */
    private function mockAuditedRoute(): AuditedRoute
    {
        try {
            $auditedRoute = $this->createMock(AuditedRoute::class);
        } catch (Exception) {
            $this->fail('Failed to create audited route mock.');
        }

        return $auditedRoute;
    }
}
