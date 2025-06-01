<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Entities;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Enums\AuditStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class AuditedRouteCollectionTest extends TestCase
{
    #[Test]
    public function it_creates_an_empty_collection_by_default(): void
    {
        $auditedRouteCollection = new AuditedRouteCollection();

        $this->assertSame(0, $auditedRouteCollection->count());
        $this->assertTrue($auditedRouteCollection->isEmpty());
    }

    #[Test]
    public function it_can_create_a_collection_from_items_via_constructor(): void
    {
        $item = $this->mockAuditedRoute('foo', 10);

        $auditedRouteCollection = new AuditedRouteCollection([$item]);

        $this->assertSame([$item], $auditedRouteCollection->get());
    }

    #[Test]
    public function it_creates_a_collection_from_items_via_factory(): void
    {
        $item = $this->mockAuditedRoute('bar', 5);

        $auditedRouteCollection = AuditedRouteCollection::make([$item]);

        $this->assertSame([$item], $auditedRouteCollection->get());
    }

    #[Test]
    public function it_can_return_the_first_item(): void
    {
        $first = $this->mockAuditedRoute('first', 1);
        $last = $this->mockAuditedRoute('last', 9);

        $auditedRouteCollection = new AuditedRouteCollection([$first, $last]);

        $this->assertSame($first, $auditedRouteCollection->first());
    }

    #[Test]
    public function it_counts_items_correctly(): void
    {
        $items = [
            $this->mockAuditedRoute('first', 1),
            $this->mockAuditedRoute('last', 9),
        ];

        $auditedRouteCollection = new AuditedRouteCollection($items);

        $this->assertSame(2, $auditedRouteCollection->count());
    }

    #[Test]
    public function it_reports_empty_and_not_empty_correctly(): void
    {
        $auditedRouteCollection = new AuditedRouteCollection();

        $this->assertTrue($auditedRouteCollection->isEmpty());
        $this->assertFalse($auditedRouteCollection->isNotEmpty());

        $auditedRouteCollection->push($this->mockAuditedRoute('first', 1));

        $this->assertFalse($auditedRouteCollection->isEmpty());
        $this->assertTrue($auditedRouteCollection->isNotEmpty());
    }

    #[Test]
    public function it_can_execute_a_callback_on_each_item(): void
    {
        $items = [
            $this->mockAuditedRoute('first', 1),
            $this->mockAuditedRoute('last', 9),
        ];
        $auditedRouteCollection = new AuditedRouteCollection($items);

        $total = 0;
        $auditedRouteCollection->each(function (AuditedRoute $route) use (&$total) {
            $total += $route->getScore() * 2;
        });

        $this->assertSame(20, $total);
    }

    #[Test]
    public function it_can_map_each_item(): void
    {
        $items = [
            $this->mockAuditedRoute('first', 1),
            $this->mockAuditedRoute('last', 9),
        ];
        $auditedRouteCollection = new AuditedRouteCollection($items);

        $scores = $auditedRouteCollection->map(fn (AuditedRoute $route): int => $route->getScore() * 2);

        $this->assertSame([2, 18], $scores);
    }

    #[Test]
    public function it_sorts_items_ascending(): void
    {
        $items = [
            $this->mockAuditedRoute('first', 1),
            $this->mockAuditedRoute('last', 3),
            $this->mockAuditedRoute('middle', 2),
        ];
        $auditedRouteCollection = new AuditedRouteCollection($items);

        $scores = $auditedRouteCollection->sort()
            ->map(fn (AuditedRoute $route): int => $route->getScore());

        $this->assertSame([1, 2, 3], $scores);
    }

    #[Test]
    public function it_sorts_items_descending(): void
    {
        $items = [
            $this->mockAuditedRoute('first', 1),
            $this->mockAuditedRoute('last', 3),
            $this->mockAuditedRoute('middle', 2),
        ];
        $auditedRouteCollection = new AuditedRouteCollection($items);

        $scores = $auditedRouteCollection->sort(ascending: false)
            ->map(fn (AuditedRoute $route): int => $route->getScore());

        $this->assertSame([3, 2, 1], $scores);
    }

    #[Test]
    public function it_filters_by_status(): void
    {
        $ok = $this->mockAuditedRoute('ok', 1, AuditStatus::Ok);
        $fail = $this->mockAuditedRoute('fail', 1, AuditStatus::Failed);
        $auditedRouteCollection = new AuditedRouteCollection([$ok, $fail]);

        $filtered = $auditedRouteCollection->where('status', AuditStatus::Ok->value);

        $this->assertSame([$ok], $filtered->get());
    }

    #[Test]
    public function it_filters_by_name(): void
    {
        $foo = $this->mockAuditedRoute('foo', 1);
        $bar = $this->mockAuditedRoute('bar', 2);
        $auditedRouteCollection = new AuditedRouteCollection([$foo, $bar]);

        $filtered = $auditedRouteCollection->where('name', 'foo');

        $this->assertSame([$foo], $filtered->get());
    }

    #[Test]
    public function it_throws_exception_for_unsupported_where_field(): void
    {
        $auditedRouteCollection = new AuditedRouteCollection([$this->mockAuditedRoute('a', 1)]);

        $this->expectException(UnexpectedValueException::class);
        $auditedRouteCollection->where('unsupported', 'value');
    }

    #[Test]
    public function it_runs_aggregators_and_returns_them(): void
    {
        $aggregator = $this->mockAggregator();
        $auditedRouteCollection = new AuditedRouteCollection([$this->mockAuditedRoute('first', 1)]);

        $result = $auditedRouteCollection->aggregate($aggregator);

        $this->assertSame([$aggregator], $result);
    }

    #[Test]
    public function it_can_be_used_as_iterator(): void
    {
        $items = [
            $this->mockAuditedRoute('first', 1),
            $this->mockAuditedRoute('last', 9),
        ];
        $auditedRouteCollection = new AuditedRouteCollection($items);

        $collected = [];
        foreach ($auditedRouteCollection as $item) {
            $collected[] = $item;
        }

        $this->assertSame($items, $collected);
    }

    /**
     * @param string $name
     * @param int $score
     * @param AuditStatus $status
     * @return AuditedRoute
     */
    private function mockAuditedRoute(string $name, int $score, AuditStatus $status = AuditStatus::Ok): AuditedRoute
    {
        try {
            $route = $this->createMock(RouteInterface::class);
        } catch (Exception) {
            $this->fail('Failed to create audited route mock.');
        }

        $route->method('getName')->willReturn($name);
        $route->method('getUri')->willReturn($name);

        $auditedRoute = $this->getMockBuilder(AuditedRoute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDisplayName', 'getScore', 'getStatus', '__toString'])
            ->getMock();

        $auditedRoute->method('getDisplayName')->willReturn($name);
        $auditedRoute->method('getScore')->willReturn($score);
        $auditedRoute->method('getStatus')->willReturn($status);
        $auditedRoute->method('__toString')->willReturn($name);

        return $auditedRoute;
    }

    /** @return AggregatorInterface */
    private function mockAggregator(): AggregatorInterface
    {
        try {
            $aggregator = $this->createMock(AggregatorInterface::class);
        } catch (Exception) {
            $this->fail('Failed to create aggregator mock.');
        }

        $aggregator->expects($this->any())->method('visit');
        $aggregator->expects($this->any())->method('after');

        return $aggregator;
    }
}
