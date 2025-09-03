<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\TotalAroundBenchmark;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TotalAroundBenchmarkTest extends TestCase
{
    #[Test]
    public function it_counts_the_total_relative_around_the_benchmark_for_all_audited_routes(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalAroundBenchmark(null, 0.5, 1.5);

        AuditRoutes::for($routes)
            ->setBenchmark(6)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(3, $aggregator->getResult());
    }

    #[Test]
    public function fraction_from_can_be_left_empty_to_count_any_value_equal_or_below_the_fraction_till(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalAroundBenchmark(null, null, 1.5);

        AuditRoutes::for($routes)
            ->setBenchmark(6)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(4, $aggregator->getResult());
    }

    #[Test]
    public function fraction_till_can_be_left_empty_to_count_any_value_equal_or_above_the_fraction_from(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalAroundBenchmark(null, 1, null);

        AuditRoutes::for($routes)
            ->setBenchmark(9)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(2, $aggregator->getResult());
    }

    #[Test]
    public function it_return_zero_when_no_routes_have_been_audited(): void
    {
        $routes = [];
        $aggregator = new TotalAroundBenchmark(null, 0.5, 1.5);

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertSame(0.0, $aggregator->getResult());
    }

    #[Test]
    public function it_does_not_take_ignored_routes_into_account(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalAroundBenchmark(null, 0.5, 1);

        AuditRoutes::for($routes)
            ->ignoreRoutes(['6'])
            ->setBenchmark(6)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(1, $aggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new TotalAroundBenchmark('Unique name', null, null);

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new TotalAroundBenchmark(null, null, null);

        $this->assertEquals('total_around_benchmark', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalAroundBenchmark('Unique name', 0.5, 1.5);

        AuditRoutes::for($routes)
            ->setBenchmark(6)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals([
            'aggregator' => 'total_around_benchmark',
            'name'       => 'Unique name',
            'result'     => 3.0,
        ], $aggregator->toArray());
    }
}
